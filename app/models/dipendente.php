<?php

class Dipendente
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function tutti()
    {
        $stmt = $this->pdo->query("SELECT d.*, u.username, u.email FROM dipendente d LEFT JOIN utente u ON u.id_utente = d.id_utente");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trova($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dipendente WHERE id_dipendente = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // public function create($data)
    // {
    //     $sql = "INSERT INTO dipendente
    //     (nome, cognome, mail, reparto, id_utente)
    //     VALUES (?, ?, ?, ?, ?)";

    //     $stmt = $this->pdo->prepare($sql);
    //     return $stmt->execute([
    //         $data['nome'],
    //         $data['cognome'],
    //         $data['mail'],
    //         $data['reparto'],
    //         $data['id_utente'],

    //     ]);
    // }

    public function aggiorna($id, $data)
    {
        $sql = "UPDATE dipendente SET
            nome = ?, 
            cognome = ?, 
            tel=?,
            reparto = ? 
            WHERE id_dipendente = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['nome'],
            $data['cognome'],
            $data['tel'],
            $data['reparto'],
            $id
        ]);
    }

    public function elimina($id)
    {
        $id = (int)$id;
        $stmtFind = $this->pdo->prepare("SELECT id_utente FROM dipendente WHERE id_dipendente = ?");
        $stmtFind->execute([$id]);
        $row = $stmtFind->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        $stmtDeleteDip = $this->pdo->prepare("DELETE FROM dipendente WHERE id_dipendente = ?");
        $stmtDeleteDip->execute([$id]);

        if (!empty($row['id_utente'])) {
            $stmtDeleteUser = $this->pdo->prepare("DELETE FROM utente WHERE id_utente = ? AND ruolo = 'dipendente'");
            $stmtDeleteUser->execute([(int)$row['id_utente']]);
        }

        return true;
    }

    // Alias retrocompatibili
    public function all() { return $this->tutti(); }
    public function find($id) { return $this->trova($id); }
    public function update($id, $data) { return $this->aggiorna($id, $data); }
    public function delete($id) { return $this->elimina($id); }
}
