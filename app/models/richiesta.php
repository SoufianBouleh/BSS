<?php

class Richiesta
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function all()
    {
        $stmt = $this->pdo->query("SELECT * FROM richiesta");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM richiesta WHERE id_richiesta = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $sql = "INSERT INTO articolo
        (data_richiesta, data_approvazione, stato , note , id_dipendente)
        VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['data_richiesta'],
            $data['data_approvazione'],
            $data['stato'],
            $data['note'],
            $data['id_dipendentte']
        ]);
    }

    public function update($id, $data)
    {
        $sql = "UPDATE `richiesta` SET `data_richiesta`=?,`data_approvazione`=? ,'stato`=?, 'note'=?
            WHERE id_richiesta?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['data_richiesta'],
            $data['data_approvazione'],
            $data['stato'],
            $data['note'],
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM richiesta WHERE id_richiesta = ?");
        return $stmt->execute([$id]);
    }
}
