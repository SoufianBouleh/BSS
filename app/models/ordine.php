<?php

class Ordine
{
    private $pdo;
    private $statiValidi = ['inviato', 'confermato', 'consegnato', 'annullato', 'rifiutato'];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function all()
    {
        return $this->allWithFornitore();
    }

    public function allWithFornitore()
    {
        $sql = "SELECT o.*, f.nome_fornitore
                FROM ordine o
                INNER JOIN fornitore f ON f.id_fornitore = o.id_fornitore
                ORDER BY o.data_ordine DESC, o.id_ordine DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ordine WHERE id_ordine = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findWithFornitore($id)
    {
        $sql = "SELECT o.*, f.nome_fornitore
                FROM ordine o
                INNER JOIN fornitore f ON f.id_fornitore = o.id_fornitore
                WHERE o.id_ordine = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findDettagli($idOrdine)
    {
        $sql = "SELECT c.id_articolo,
                       a.nome_articolo,
                       a.unita_misura,
                       c.quantita_ordinata,
                       c.prezzo AS totale_riga,
                       CASE
                           WHEN c.quantita_ordinata > 0 AND c.prezzo IS NOT NULL
                           THEN ROUND(c.prezzo / c.quantita_ordinata, 2)
                           ELSE a.prezzo_unitario
                       END AS prezzo_unitario
                FROM comprende c
                INNER JOIN articolo a ON a.id_articolo = c.id_articolo
                WHERE c.id_ordine = ?
                ORDER BY a.nome_articolo ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$idOrdine]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFornitori()
    {
        $stmt = $this->pdo->query("SELECT id_fornitore, nome_fornitore FROM fornitore ORDER BY nome_fornitore ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getArticoli()
    {
        $sql = "SELECT id_articolo, nome_articolo, prezzo_unitario, unita_misura
                FROM articolo
                ORDER BY nome_articolo ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $dataOrdine = $this->normalizzaDatiOrdine($data);
        $costoTotale = isset($data['costo_totale']) ? (float)$data['costo_totale'] : 0;
        $sql = "INSERT INTO ordine
                (data_ordine, data_consegna_prevista, data_consegna_effettiva, stato_ordine, costo_totale, id_fornitore)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $dataOrdine['data_ordine'],
            $dataOrdine['data_consegna_prevista'],
            $dataOrdine['data_consegna_effettiva'],
            $dataOrdine['stato_ordine'],
            $costoTotale,
            $dataOrdine['id_fornitore']
        ]);
    }

    public function createWithItems($data, $righe = [])
    {
        $dataOrdine = $this->normalizzaDatiOrdine($data);
        $righePulite = $this->normalizzaRighe($righe);

        if (empty($righePulite)) {
            throw new InvalidArgumentException('Inserisci almeno un articolo con quantita maggiore di zero.');
        }

        $this->pdo->beginTransaction();

        try {
            $sqlOrdine = "INSERT INTO ordine
                (data_ordine, data_consegna_prevista, data_consegna_effettiva, stato_ordine, costo_totale, id_fornitore)
                VALUES (?, ?, ?, ?, 0, ?)";
            $stmtOrdine = $this->pdo->prepare($sqlOrdine);
            $stmtOrdine->execute([
                $dataOrdine['data_ordine'],
                $dataOrdine['data_consegna_prevista'],
                $dataOrdine['data_consegna_effettiva'],
                $dataOrdine['stato_ordine'],
                $dataOrdine['id_fornitore']
            ]);

            $idOrdine = (int)$this->pdo->lastInsertId();
            $prezzi = $this->prezziArticoliMap(array_column($righePulite, 'id_articolo'));

            $sqlRiga = "INSERT INTO comprende (id_ordine, id_articolo, quantita_ordinata, prezzo)
                        VALUES (?, ?, ?, ?)";
            $stmtRiga = $this->pdo->prepare($sqlRiga);

            $totale = 0.0;
            foreach ($righePulite as $riga) {
                if (!isset($prezzi[$riga['id_articolo']])) {
                    throw new InvalidArgumentException('Articolo non valido nella composizione ordine.');
                }

                $prezzoUnitario = (float)$prezzi[$riga['id_articolo']];
                $totaleRiga = round($prezzoUnitario * $riga['quantita'], 2);
                $totale += $totaleRiga;

                $stmtRiga->execute([
                    $idOrdine,
                    $riga['id_articolo'],
                    $riga['quantita'],
                    $totaleRiga
                ]);
            }

            $stmtTotale = $this->pdo->prepare("UPDATE ordine SET costo_totale = ? WHERE id_ordine = ?");
            $stmtTotale->execute([round($totale, 2), $idOrdine]);

            $this->pdo->commit();
            return $idOrdine;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function update($id, $data)
    {
        return $this->updateBase($id, $data);
    }

    public function updateBase($id, $data)
    {
        $id = (int)$id;
        $attuale = $this->find($id);
        if (!$attuale) {
            throw new InvalidArgumentException('Ordine non trovato.');
        }

        if ($attuale['stato_ordine'] === 'confermato') {
            throw new RuntimeException('Ordine confermato: modifica non consentita.');
        }

        $dataOrdine = $this->normalizzaDatiOrdine($data);
        $sql = "UPDATE ordine
                SET data_ordine = ?,
                    data_consegna_prevista = ?,
                    data_consegna_effettiva = ?,
                    stato_ordine = ?,
                    id_fornitore = ?
                WHERE id_ordine = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $dataOrdine['data_ordine'],
            $dataOrdine['data_consegna_prevista'],
            $dataOrdine['data_consegna_effettiva'],
            $dataOrdine['stato_ordine'],
            $dataOrdine['id_fornitore'],
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM ordine WHERE id_ordine = ?");
        return $stmt->execute([(int)$id]);
    }

    public function deleteIfInviato($id)
    {
        $id = (int)$id;
        $stmt = $this->pdo->prepare("DELETE FROM ordine WHERE id_ordine = ? AND stato_ordine = 'inviato'");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    private function normalizzaDatiOrdine($data)
    {
        $stato = isset($data['stato_ordine']) ? trim((string)$data['stato_ordine']) : 'inviato';
        if (!in_array($stato, $this->statiValidi, true)) {
            $stato = 'inviato';
        }

        $dataOrdine = $this->normalizzaData($data['data_ordine'] ?? '');
        if ($dataOrdine === null) {
            throw new InvalidArgumentException('La data ordine e obbligatoria.');
        }

        $idFornitore = (int)($data['id_fornitore'] ?? 0);
        if ($idFornitore <= 0) {
            throw new InvalidArgumentException('Seleziona un fornitore valido.');
        }

        return [
            'data_ordine' => $dataOrdine,
            'data_consegna_prevista' => $this->normalizzaData($data['data_consegna_prevista'] ?? null),
            'data_consegna_effettiva' => $this->normalizzaData($data['data_consegna_effettiva'] ?? null),
            'stato_ordine' => $stato,
            'id_fornitore' => $idFornitore
        ];
    }

    private function normalizzaRighe($righe)
    {
        $pulite = [];

        foreach ($righe as $riga) {
            $idArticolo = (int)($riga['id_articolo'] ?? 0);
            $quantita = (int)($riga['quantita'] ?? 0);
            if ($idArticolo > 0 && $quantita > 0) {
                $pulite[] = [
                    'id_articolo' => $idArticolo,
                    'quantita' => $quantita
                ];
            }
        }

        return $pulite;
    }

    private function prezziArticoliMap($ids)
    {
        if (empty($ids)) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT id_articolo, prezzo_unitario FROM articolo WHERE id_articolo IN ($placeholders)");
        $stmt->execute($ids);
        $righe = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $mappa = [];
        foreach ($righe as $riga) {
            $mappa[(int)$riga['id_articolo']] = (float)$riga['prezzo_unitario'];
        }
        return $mappa;
    }

    private function normalizzaData($value)
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        return $value;
    }
}
