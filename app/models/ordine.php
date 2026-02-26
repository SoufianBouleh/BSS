<?php

class Ordine
{
    private $pdo;
    private $statiValidi = ['inviato', 'confermato', 'consegnato', 'annullato', 'rifiutato'];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function tutti()
    {
        return $this->tuttiConFornitore();
    }

    public function tuttiConFornitore($filters = [])
    {
        $sql = "SELECT ordine.*, fornitore.nome_fornitore
                FROM ordine
                INNER JOIN fornitore ON fornitore.id_fornitore = ordine.id_fornitore";
        $where = [];
        $params = [];

        if (!empty($filters['stato']) && in_array($filters['stato'], $this->statiValidi, true)) {
            $where[] = "ordine.stato_ordine = :stato";
            $params[':stato'] = $filters['stato'];
        }

        if (!empty($filters['id_fornitore'])) {
            $where[] = "ordine.id_fornitore = :id_fornitore";
            $params[':id_fornitore'] = (int)$filters['id_fornitore'];
        }

        if (!empty($filters['dal'])) {
            $where[] = "ordine.data_ordine >= :dal";
            $params[':dal'] = $filters['dal'];
        }

        if (!empty($filters['al'])) {
            $where[] = "ordine.data_ordine <= :al";
            $params[':al'] = $filters['al'];
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY ordine.data_ordine DESC, ordine.id_ordine DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contaPerStato($stato)
    {
        if (!in_array($stato, $this->statiValidi, true)) {
            return 0;
        }
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM ordine WHERE stato_ordine = ?");
        $stmt->execute([$stato]);
        return (int)$stmt->fetchColumn();
    }

    public function trova($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ordine WHERE id_ordine = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function trovaConFornitore($id)
    {
        $sql = "SELECT ordine.*, fornitore.nome_fornitore
                FROM ordine
                INNER JOIN fornitore ON fornitore.id_fornitore = ordine.id_fornitore
                WHERE ordine.id_ordine = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function trovaDettagli($idOrdine)
    {
        $sql = "SELECT comprende.id_articolo,
                       articolo.nome_articolo,
                       articolo.unita_misura,
                       comprende.quantita_ordinata,
                       comprende.prezzo AS totale_riga,
                       CASE
                           WHEN comprende.quantita_ordinata > 0 AND comprende.prezzo IS NOT NULL
                           THEN ROUND(comprende.prezzo / comprende.quantita_ordinata, 2)
                           ELSE articolo.prezzo_unitario
                       END AS prezzo_unitario
                FROM comprende
                INNER JOIN articolo ON articolo.id_articolo = comprende.id_articolo
                WHERE comprende.id_ordine = ?
                ORDER BY articolo.nome_articolo ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$idOrdine]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function elencoFornitori()
    {
        $stmt = $this->pdo->query("SELECT id_fornitore, nome_fornitore FROM fornitore ORDER BY nome_fornitore ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function elencoArticoli()
    {
        $sql = "SELECT id_articolo, nome_articolo, prezzo_unitario, unita_misura
                FROM articolo
                ORDER BY nome_articolo ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function creaConRighe($data, $righe = [])
    {
        $dataOrdine = $this->normalizzaDatiOrdine($data);
        $righePulite = $this->normalizzaRighe($righe);

        if (empty($righePulite)) {
            throw new InvalidArgumentException('Inserisci almeno un articolo con quantita maggiore di zero.');
        }

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
        return $idOrdine;
    }

    public function aggiornaBase($id, $data)
    {
        $id = (int)$id;
        $attuale = $this->trova($id);
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
        $stmtCheck = $this->pdo->prepare("SELECT stato_ordine FROM ordine WHERE id_ordine = ?");
        $stmtCheck->execute([$id]);
        $ordine = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$ordine || $ordine['stato_ordine'] !== 'inviato') {
            return false;
        }

        $sqlStock = "UPDATE articolo
                     INNER JOIN comprende ON comprende.id_articolo = articolo.id_articolo
                     SET articolo.quantita_in_stock = articolo.quantita_in_stock + comprende.quantita_ordinata
                     WHERE comprende.id_ordine = ?";
        $stmtStock = $this->pdo->prepare($sqlStock);
        $stmtStock->execute([$id]);

        $stmtOrdine = $this->pdo->prepare("UPDATE ordine
                                           SET stato_ordine = 'confermato',
                                               data_consegna_effettiva = CURDATE()
                                           WHERE id_ordine = ?");
        $stmtOrdine->execute([$id]);
        return true;
    }

    public function eliminaStorico($id)
    {
        $id = (int)$id;
        $stmt = $this->pdo->prepare("DELETE FROM ordine
                                     WHERE id_ordine = ?
                                       AND stato_ordine IN ('confermato', 'rifiutato', 'annullato', 'consegnato')");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function creaAutomaticiDaScorteCritiche()
    {
        $sql = "SELECT articolo.id_articolo,
                       articolo.quantita_in_stock,
                       articolo.punto_riordino,
                       articolo.id_fornitore_preferito
                FROM articolo
                WHERE articolo.id_fornitore_preferito IS NOT NULL
                  AND articolo.id_fornitore_preferito > 0
                  AND articolo.quantita_in_stock < articolo.punto_riordino";
        $articoli = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $creati = 0;
        $stmtCheck = $this->pdo->prepare("SELECT COUNT(*)
                                          FROM ordine
                                          INNER JOIN comprende ON comprende.id_ordine = ordine.id_ordine
                                          WHERE comprende.id_articolo = ?
                                            AND ordine.stato_ordine = 'inviato'");

        foreach ($articoli as $a) {
            $idArticolo = (int)$a['id_articolo'];
            $idFornitore = (int)$a['id_fornitore_preferito'];

            $stmtCheck->execute([$idArticolo]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                continue;
            }

            $quantita = max(1, ((int)$a['punto_riordino'] * 2) - (int)$a['quantita_in_stock']);
            $this->creaConRighe([
                'data_ordine' => date('Y-m-d'),
                'data_consegna_prevista' => date('Y-m-d', strtotime('+7 days')),
                'id_fornitore' => $idFornitore
            ], [[
                'id_articolo' => $idArticolo,
                'quantita' => $quantita
            ]]);
            $creati++;
        }

        return $creati;
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
