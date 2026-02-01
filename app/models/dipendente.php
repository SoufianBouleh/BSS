<?php

class Dipendente
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function all()
    {
        $stmt = $this->pdo->query("SELECT * FROM dipendente");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
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

    public function update($id, $data)
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

    public function delete($id)
    {

        //da fare
    }
}
