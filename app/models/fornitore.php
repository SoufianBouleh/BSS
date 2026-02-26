<?php

class Fornitore
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function tutti()
    {
        $stmt = $this->pdo->query("SELECT * FROM fornitore");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trova($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM fornitore WHERE id_fornitore = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crea($data)
    {
        $sql = "INSERT INTO fornitore
        (nome_fornitore, cf,mail, indirizzo, citta, tel, p_iva, iban)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['nome_fornitore'],
            $data['cf'],
            $data['mail'],
            $data['indirizzo'],
            $data['citta'],
            $data['tel'],
            $data['p_iva'],
            $data['iban'],
        ]);
    }

    public function aggiorna($id, $data)
    {
        $sql = "UPDATE fornitore SET
        nome_fornitore=?, cf=?,mail=?, indirizzo=?, citta=?, tel=?, p_iva=?, iban=?
            WHERE id_fornitore = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['nome_fornitore'],
            $data['cf'],
            $data['mail'],
            $data['indirizzo'],
            $data['citta'],
            $data['tel'],
            $data['p_iva'],
            $data['iban'],
            $id
        ]);
    }

    public function elimina($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM fornitore WHERE id_fornitore = ?");
        return $stmt->execute([$id]);

        //set null per ordine  e articolo e mettere coalessing in caso di null
    }

    // Alias retrocompatibili
    public function all() { return $this->tutti(); }
    public function find($id) { return $this->trova($id); }
    public function create($data) { return $this->crea($data); }
    public function update($id, $data) { return $this->aggiorna($id, $data); }
    public function delete($id) { return $this->elimina($id); }
}
