<?php

class Richiesta
{
    private $pdo;
    private $statiValidi = ['in_attesa', 'approvata', 'respinta', 'evasa'];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function all()
    {
        return $this->allForAdmin();
    }

    public function allForAdmin($filters = [])
    {
        $sql = "SELECT r.*,
                       d.nome,
                       d.cognome,
                       d.reparto,
                       COUNT(c.id_articolo) AS righe,
                       COALESCE(SUM(c.quantita_richiesta), 0) AS quantita_totale
                FROM richiesta r
                INNER JOIN dipendente d ON d.id_dipendente = r.id_dipendente
                LEFT JOIN contiene c ON c.id_richiesta = r.id_richiesta
                WHERE 1=1";

        $params = [];

        if (!empty($filters['stato']) && in_array($filters['stato'], $this->statiValidi, true)) {
            $sql .= " AND r.stato = :stato";
            $params[':stato'] = $filters['stato'];
        }

        if (!empty($filters['id_dipendente'])) {
            $sql .= " AND r.id_dipendente = :id_dipendente";
            $params[':id_dipendente'] = (int)$filters['id_dipendente'];
        }

        if (!empty($filters['q'])) {
            $sql .= " AND (
                d.nome LIKE :q OR
                d.cognome LIKE :q OR
                d.reparto LIKE :q OR
                r.note LIKE :q OR
                CAST(r.id_richiesta AS CHAR) LIKE :q
            )";
            $params[':q'] = '%' . trim((string)$filters['q']) . '%';
        }

        if (!empty($filters['dal'])) {
            $sql .= " AND DATE(r.data_richiesta) >= :dal";
            $params[':dal'] = $filters['dal'];
        }

        if (!empty($filters['al'])) {
            $sql .= " AND DATE(r.data_richiesta) <= :al";
            $params[':al'] = $filters['al'];
        }

        $sql .= " GROUP BY r.id_richiesta, d.nome, d.cognome, d.reparto
                  ORDER BY r.data_richiesta DESC, r.id_richiesta DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function allByDipendente($idDipendente)
    {
        $sql = "SELECT r.*,
                       COUNT(c.id_articolo) AS righe,
                       COALESCE(SUM(c.quantita_richiesta), 0) AS quantita_totale
                FROM richiesta r
                LEFT JOIN contiene c ON c.id_richiesta = r.id_richiesta
                WHERE r.id_dipendente = ?
                GROUP BY r.id_richiesta
                ORDER BY r.data_richiesta DESC, r.id_richiesta DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$idDipendente]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDipendenteIdByUtente($idUtente)
    {
        $stmt = $this->pdo->prepare("SELECT id_dipendente FROM dipendente WHERE id_utente = ?");
        $stmt->execute([(int)$idUtente]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    public function find($id)
    {
        return $this->findWithDetails($id);
    }

    public function findWithDetails($id)
    {
        $sql = "SELECT r.*,
                       d.nome,
                       d.cognome,
                       d.reparto
                FROM richiesta r
                INNER JOIN dipendente d ON d.id_dipendente = r.id_dipendente
                WHERE r.id_richiesta = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([(int)$id]);
        $richiesta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$richiesta) {
            return null;
        }

        $sqlDettagli = "SELECT c.id_articolo,
                               c.quantita_richiesta,
                               c.descrizione,
                               c.urgente,
                               a.nome_articolo,
                               a.unita_misura
                        FROM contiene c
                        INNER JOIN articolo a ON a.id_articolo = c.id_articolo
                        WHERE c.id_richiesta = ?
                        ORDER BY a.nome_articolo ASC";
        $stmtDettagli = $this->pdo->prepare($sqlDettagli);
        $stmtDettagli->execute([(int)$id]);
        $richiesta['dettagli'] = $stmtDettagli->fetchAll(PDO::FETCH_ASSOC);

        return $richiesta;
    }

    public function updateStato($id, $stato, $note = null)
    {
        $id = (int)$id;
        $stato = trim((string)$stato);

        if (!in_array($stato, $this->statiValidi, true)) {
            throw new InvalidArgumentException('Stato richiesta non valido.');
        }

        $attuale = $this->find($id);
        if (!$attuale) {
            throw new InvalidArgumentException('Richiesta non trovata.');
        }

        $dataApprovazione = null;
        if ($stato === 'approvata' || $stato === 'evasa') {
            $dataApprovazione = date('Y-m-d H:i:s');
        }

        $noteFinali = $attuale['note'] ?? '';
        $note = trim((string)$note);
        if ($note !== '') {
            $noteFinali = $note;
        }

        $stmt = $this->pdo->prepare("UPDATE richiesta
                                     SET stato = ?, data_approvazione = ?, note = ?
                                     WHERE id_richiesta = ?");
        return $stmt->execute([$stato, $dataApprovazione, $noteFinali, $id]);
    }

    public function createByDipendente($idDipendente, $note, $idArticolo = 0, $quantita = 1, $descrizione = '', $urgente = 0)
    {
        $idArticolo = (int)$idArticolo;
        $quantita = max(1, (int)$quantita);
        if ($idArticolo <= 0) {
            throw new InvalidArgumentException('Seleziona un articolo.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("INSERT INTO richiesta (stato, note, id_dipendente) VALUES ('in_attesa', ?, ?)");
            $stmt->execute([trim((string)$note), (int)$idDipendente]);
            $idRichiesta = (int)$this->pdo->lastInsertId();

            $stmtContiene = $this->pdo->prepare("INSERT INTO contiene (id_richiesta, id_articolo, quantita_richiesta, descrizione, urgente)
                                                 VALUES (?, ?, ?, ?, ?)");
            $stmtContiene->execute([$idRichiesta, $idArticolo, $quantita, trim((string)$descrizione), (int)$urgente === 1 ? 1 : 0]);
            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateByDipendente($idRichiesta, $idDipendente, $note, $idArticolo = 0, $quantita = 1, $descrizione = '', $urgente = 0)
    {
        $idArticolo = (int)$idArticolo;
        $quantita = max(1, (int)$quantita);
        if ($idArticolo <= 0) {
            throw new InvalidArgumentException('Seleziona un articolo.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE richiesta
                                         SET note = ?, stato = 'in_attesa', data_approvazione = NULL
                                         WHERE id_richiesta = ? AND id_dipendente = ? AND stato IN ('in_attesa', 'respinta')");
            $stmt->execute([trim((string)$note), (int)$idRichiesta, (int)$idDipendente]);
            if ($stmt->rowCount() <= 0) {
                $this->pdo->rollBack();
                return false;
            }

            $stmtDelete = $this->pdo->prepare("DELETE FROM contiene WHERE id_richiesta = ?");
            $stmtDelete->execute([(int)$idRichiesta]);

            $stmtContiene = $this->pdo->prepare("INSERT INTO contiene (id_richiesta, id_articolo, quantita_richiesta, descrizione, urgente)
                                                 VALUES (?, ?, ?, ?, ?)");
            $stmtContiene->execute([(int)$idRichiesta, $idArticolo, $quantita, trim((string)$descrizione), (int)$urgente === 1 ? 1 : 0]);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM richiesta WHERE id_richiesta = ?");
        return $stmt->execute([(int)$id]);
    }
}
