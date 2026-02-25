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

    public function allWithFornitore($filters = [])
    {
        $sql = "SELECT o.*, f.nome_fornitore
                FROM ordine o
                INNER JOIN fornitore f ON f.id_fornitore = o.id_fornitore
                WHERE 1=1";
        $params = [];

        if (!empty($filters['stato']) && in_array($filters['stato'], $this->statiValidi, true)) {
            $sql .= " AND o.stato_ordine = :stato";
            $params[':stato'] = $filters['stato'];
        }

        if (!empty($filters['id_fornitore'])) {
            $sql .= " AND o.id_fornitore = :id_fornitore";
            $params[':id_fornitore'] = (int)$filters['id_fornitore'];
        }

        if (!empty($filters['q'])) {
            $sql .= " AND (
                f.nome_fornitore LIKE :q OR
                CAST(o.id_ordine AS CHAR) LIKE :q
            )";
            $params[':q'] = '%' . trim((string)$filters['q']) . '%';
        }

        if (!empty($filters['dal'])) {
            $sql .= " AND o.data_ordine >= :dal";
            $params[':dal'] = $filters['dal'];
        }

        if (!empty($filters['al'])) {
            $sql .= " AND o.data_ordine <= :al";
            $params[':al'] = $filters['al'];
        }

        $sql .= " ORDER BY o.data_ordine DESC, o.id_ordine DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByStato($stato)
    {
        if (!in_array($stato, $this->statiValidi, true)) {
            return 0;
        }
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM ordine WHERE stato_ordine = ?");
        $stmt->execute([$stato]);
        return (int)$stmt->fetchColumn();
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
                VALUES (?, ?, NULL, 'inviato', 0, ?)";
            $stmtOrdine = $this->pdo->prepare($sqlOrdine);
            $stmtOrdine->execute([
                $dataOrdine['data_ordine'],
                $dataOrdine['data_consegna_prevista'],
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

    public function updateBase($id, $data)
    {
        $id = (int)$id;
        $attuale = $this->find($id);
        if (!$attuale) {
            throw new InvalidArgumentException('Ordine non trovato.');
        }

        if ($attuale['stato_ordine'] !== 'rifiutato') {
            throw new RuntimeException('Si possono modificare solo ordini rifiutati.');
        }

        $dataOrdine = $this->normalizzaDatiOrdine($data);
        $sql = "UPDATE ordine
                SET data_ordine = ?,
                    data_consegna_prevista = ?,
                    data_consegna_effettiva = NULL,
                    stato_ordine = 'inviato',
                    id_fornitore = ?
                WHERE id_ordine = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $dataOrdine['data_ordine'],
            $dataOrdine['data_consegna_prevista'],
            $dataOrdine['id_fornitore'],
            $id
        ]);
    }

    public function annulla($id)
    {
        $id = (int)$id;
        $stmt = $this->pdo->prepare("UPDATE ordine SET stato_ordine = 'annullato' WHERE id_ordine = ? AND stato_ordine = 'inviato'");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function rifiuta($id)
    {
        $id = (int)$id;
        $stmt = $this->pdo->prepare("UPDATE ordine SET stato_ordine = 'rifiutato' WHERE id_ordine = ? AND stato_ordine = 'inviato'");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function conferma($id)
    {
        $id = (int)$id;
        $this->pdo->beginTransaction();

        try {
            $stmtCheck = $this->pdo->prepare("SELECT stato_ordine FROM ordine WHERE id_ordine = ? FOR UPDATE");
            $stmtCheck->execute([$id]);
            $ordine = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$ordine || $ordine['stato_ordine'] !== 'inviato') {
                $this->pdo->rollBack();
                return false;
            }

            $sqlStock = "UPDATE articolo a
                         INNER JOIN comprende c ON c.id_articolo = a.id_articolo
                         SET a.quantita_in_stock = a.quantita_in_stock + c.quantita_ordinata
                         WHERE c.id_ordine = ?";
            $stmtStock = $this->pdo->prepare($sqlStock);
            $stmtStock->execute([$id]);

            $stmtOrdine = $this->pdo->prepare("UPDATE ordine
                                               SET stato_ordine = 'confermato',
                                                   data_consegna_effettiva = CURDATE()
                                               WHERE id_ordine = ?");
            $stmtOrdine->execute([$id]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteStorico($id)
    {
        $id = (int)$id;
        $stmt = $this->pdo->prepare("DELETE FROM ordine
                                     WHERE id_ordine = ?
                                       AND stato_ordine IN ('confermato', 'rifiutato', 'annullato', 'consegnato')");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    private function normalizzaDatiOrdine($data)
    {
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
