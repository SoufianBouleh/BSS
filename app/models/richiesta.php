<?php

class Richiesta
{
    private $pdo;
    private $statiValidi = ['in_attesa', 'approvata', 'respinta', 'evasa'];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function tutti()
    {
        return $this->tuttePerAdmin();
    }

    public function tuttePerAdmin($filters = [])
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

    public function tuttePerDipendente($idDipendente)
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

    public function trovaIdDipendenteDaUtente($idUtente)
    {
        $stmt = $this->pdo->prepare("SELECT id_dipendente FROM dipendente WHERE id_utente = ?");
        $stmt->execute([(int)$idUtente]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    public function trova($id)
    {
        return $this->trovaConDettagli($id);
    }

    public function trovaConDettagli($id)
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

    public function aggiornaStato($id, $stato, $note = null)
    {
        $id = (int)$id;
        $stato = trim((string)$stato);

        if (!in_array($stato, $this->statiValidi, true)) {
            throw new InvalidArgumentException('Stato richiesta non valido.');
        }

        $attuale = $this->trova($id);
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

    public function approvaECreaOrdini($idRichiesta)
    {
        $idRichiesta = (int)$idRichiesta;
        $richiesta = $this->trovaConDettagli($idRichiesta);
        if (!$richiesta) {
            return ['ok' => false, 'errore' => 'Richiesta non trovata.'];
        }

        $statoAttuale = (string)($richiesta['stato'] ?? '');
        if (!in_array($statoAttuale, ['in_attesa', 'respinta'], true)) {
            return ['ok' => false, 'errore' => 'Stato richiesta non valido per approvazione.'];
        }

        $dettagli = $richiesta['dettagli'] ?? [];
        if (empty($dettagli)) {
            return ['ok' => false, 'errore' => 'La richiesta non contiene articoli.'];
        }

        $idsArticoli = [];
        foreach ($dettagli as $riga) {
            $idArticolo = (int)($riga['id_articolo'] ?? 0);
            if ($idArticolo > 0) {
                $idsArticoli[] = $idArticolo;
            }
        }
        $idsArticoli = array_values(array_unique($idsArticoli));
        if (empty($idsArticoli)) {
            return ['ok' => false, 'errore' => 'Nessun articolo valido nella richiesta.'];
        }

        $placeholders = implode(',', array_fill(0, count($idsArticoli), '?'));
        $stmtArticoli = $this->pdo->prepare("SELECT id_articolo, id_fornitore_preferito
                                             FROM articolo
                                             WHERE id_articolo IN ($placeholders)");
        $stmtArticoli->execute($idsArticoli);
        $mappaFornitore = [];
        foreach ($stmtArticoli->fetchAll(PDO::FETCH_ASSOC) as $a) {
            $mappaFornitore[(int)$a['id_articolo']] = (int)($a['id_fornitore_preferito'] ?? 0);
        }

        $righePerFornitore = [];
        $saltate = 0;
        foreach ($dettagli as $riga) {
            $idArticolo = (int)($riga['id_articolo'] ?? 0);
            $quantita = (int)($riga['quantita_richiesta'] ?? 0);
            $idFornitore = (int)($mappaFornitore[$idArticolo] ?? 0);
            if ($idArticolo <= 0 || $quantita <= 0 || $idFornitore <= 0) {
                $saltate++;
                continue;
            }
            if (!isset($righePerFornitore[$idFornitore])) {
                $righePerFornitore[$idFornitore] = [];
            }
            $righePerFornitore[$idFornitore][] = [
                'id_articolo' => $idArticolo,
                'quantita' => $quantita
            ];
        }

        if (empty($righePerFornitore)) {
            return ['ok' => false, 'errore' => 'Nessun articolo ordinabile: imposta fornitore preferito sugli articoli richiesti.'];
        }

        require_once __DIR__ . '/ordine.php';
        $ordineModel = new Ordine($this->pdo);
        $ordiniCreati = [];
        foreach ($righePerFornitore as $idFornitore => $righe) {
            $idOrdine = $ordineModel->creaConRighe([
                'data_ordine' => date('Y-m-d'),
                'data_consegna_prevista' => date('Y-m-d', strtotime('+7 days')),
                'id_fornitore' => (int)$idFornitore
            ], $righe);
            $ordiniCreati[] = (int)$idOrdine;
        }

        if (empty($ordiniCreati)) {
            return ['ok' => false, 'errore' => 'Nessun ordine creato.'];
        }

        $noteBase = trim((string)($richiesta['note'] ?? ''));
        $testoOrdini = 'Ordini creati: #' . implode(', #', $ordiniCreati);
        if ($saltate > 0) {
            $testoOrdini .= " (righe saltate: {$saltate})";
        }
        $noteFinali = $noteBase === '' ? $testoOrdini : ($noteBase . ' | ' . $testoOrdini);

        $stmtAggiorna = $this->pdo->prepare("UPDATE richiesta
                                             SET stato = ?, data_approvazione = ?, note = ?
                                             WHERE id_richiesta = ?");
        $stmtAggiorna->execute(['approvata', date('Y-m-d H:i:s'), $noteFinali, $idRichiesta]);

        return [
            'ok' => true,
            'ordini' => $ordiniCreati,
            'saltate' => $saltate
        ];
    }

    public function approvaECreaOrdineSemplice($idRichiesta)
    {
        $idRichiesta = (int)$idRichiesta;
        $richiesta = $this->trovaConDettagli($idRichiesta);
        if (!$richiesta) {
            return ['ok' => false, 'errore' => 'Richiesta non trovata.'];
        }

        $statoAttuale = (string)($richiesta['stato'] ?? '');
        if (!in_array($statoAttuale, ['in_attesa', 'respinta'], true)) {
            return ['ok' => false, 'errore' => 'Richiesta non approvabile in questo stato.'];
        }

        $dettagli = $richiesta['dettagli'] ?? [];
        if (empty($dettagli)) {
            return ['ok' => false, 'errore' => 'La richiesta non contiene articoli.'];
        }

        $primaRiga = null;
        foreach ($dettagli as $riga) {
            if ((int)($riga['id_articolo'] ?? 0) > 0 && (int)($riga['quantita_richiesta'] ?? 0) > 0) {
                $primaRiga = $riga;
                break;
            }
        }
        if ($primaRiga === null) {
            return ['ok' => false, 'errore' => 'Nessun articolo valido nella richiesta.'];
        }

        $idArticolo = (int)$primaRiga['id_articolo'];
        $quantita = (int)$primaRiga['quantita_richiesta'];

        $stmtArt = $this->pdo->prepare("SELECT id_fornitore_preferito FROM articolo WHERE id_articolo = ?");
        $stmtArt->execute([$idArticolo]);
        $idFornitore = (int)$stmtArt->fetchColumn();
        if ($idFornitore <= 0) {
            return ['ok' => false, 'errore' => 'L\'articolo della richiesta non ha fornitore preferito.'];
        }

        require_once __DIR__ . '/ordine.php';
        $ordineModel = new Ordine($this->pdo);
        $idOrdine = $ordineModel->creaConRighe([
            'data_ordine' => date('Y-m-d'),
            'data_consegna_prevista' => date('Y-m-d', strtotime('+7 days')),
            'id_fornitore' => $idFornitore
        ], [[
            'id_articolo' => $idArticolo,
            'quantita' => $quantita
        ]]);

        $noteBase = trim((string)($richiesta['note'] ?? ''));
        $notaOrdine = "Ordine creato: #{$idOrdine}";
        $noteFinali = $noteBase === '' ? $notaOrdine : ($noteBase . ' | ' . $notaOrdine);

        $stmtAggiorna = $this->pdo->prepare("UPDATE richiesta
                                             SET stato = ?, data_approvazione = ?, note = ?
                                             WHERE id_richiesta = ?");
        $stmtAggiorna->execute(['approvata', date('Y-m-d H:i:s'), $noteFinali, $idRichiesta]);

        return ['ok' => true, 'id_ordine' => (int)$idOrdine];
    }

    public function creaDaDipendente($idDipendente, $note, $idArticolo = 0, $quantita = 1, $descrizione = '', $urgente = 0)
    {
        $idArticolo = (int)$idArticolo;
        $quantita = max(1, (int)$quantita);
        if ($idArticolo <= 0) {
            throw new InvalidArgumentException('Seleziona un articolo.');
        }

        $stmt = $this->pdo->prepare("INSERT INTO richiesta (stato, note, id_dipendente) VALUES ('in_attesa', ?, ?)");
        $stmt->execute([trim((string)$note), (int)$idDipendente]);
        $idRichiesta = (int)$this->pdo->lastInsertId();

        $stmtContiene = $this->pdo->prepare("INSERT INTO contiene (id_richiesta, id_articolo, quantita_richiesta, descrizione, urgente)
                                             VALUES (?, ?, ?, ?, ?)");
        $stmtContiene->execute([$idRichiesta, $idArticolo, $quantita, trim((string)$descrizione), (int)$urgente === 1 ? 1 : 0]);
        return true;
    }

    public function aggiornaDaDipendente($idRichiesta, $idDipendente, $note, $idArticolo = 0, $quantita = 1, $descrizione = '', $urgente = 0)
    {
        $idArticolo = (int)$idArticolo;
        $quantita = max(1, (int)$quantita);
        if ($idArticolo <= 0) {
            throw new InvalidArgumentException('Seleziona un articolo.');
        }

        $stmt = $this->pdo->prepare("UPDATE richiesta
                                     SET note = ?, stato = 'in_attesa', data_approvazione = NULL
                                     WHERE id_richiesta = ? AND id_dipendente = ? AND stato IN ('in_attesa', 'respinta')");
        $stmt->execute([trim((string)$note), (int)$idRichiesta, (int)$idDipendente]);
        if ($stmt->rowCount() <= 0) {
            return false;
        }

        $stmtDelete = $this->pdo->prepare("DELETE FROM contiene WHERE id_richiesta = ?");
        $stmtDelete->execute([(int)$idRichiesta]);

        $stmtContiene = $this->pdo->prepare("INSERT INTO contiene (id_richiesta, id_articolo, quantita_richiesta, descrizione, urgente)
                                             VALUES (?, ?, ?, ?, ?)");
        $stmtContiene->execute([(int)$idRichiesta, $idArticolo, $quantita, trim((string)$descrizione), (int)$urgente === 1 ? 1 : 0]);
        return true;
    }

    public function elimina($id)
    {
        $id = (int)$id;
        $stmtDett = $this->pdo->prepare("DELETE FROM contiene WHERE id_richiesta = ?");
        $stmtDett->execute([$id]);
        $stmt = $this->pdo->prepare("DELETE FROM richiesta WHERE id_richiesta = ?");
        return $stmt->execute([$id]);
    }

    // Alias retrocompatibili
    public function all() { return $this->tutti(); }
    public function allForAdmin($filters = []) { return $this->tuttePerAdmin($filters); }
    public function allByDipendente($idDipendente) { return $this->tuttePerDipendente($idDipendente); }
    public function getDipendenteIdByUtente($idUtente) { return $this->trovaIdDipendenteDaUtente($idUtente); }
    public function find($id) { return $this->trova($id); }
    public function findWithDetails($id) { return $this->trovaConDettagli($id); }
    public function updateStato($id, $stato, $note = null) { return $this->aggiornaStato($id, $stato, $note); }
    public function createByDipendente($idDipendente, $note, $idArticolo = 0, $quantita = 1, $descrizione = '', $urgente = 0) { return $this->creaDaDipendente($idDipendente, $note, $idArticolo, $quantita, $descrizione, $urgente); }
    public function updateByDipendente($idRichiesta, $idDipendente, $note, $idArticolo = 0, $quantita = 1, $descrizione = '', $urgente = 0) { return $this->aggiornaDaDipendente($idRichiesta, $idDipendente, $note, $idArticolo, $quantita, $descrizione, $urgente); }
    public function delete($id) { return $this->elimina($id); }
}
