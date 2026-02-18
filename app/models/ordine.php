<?php

class Ordine
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function all()
    {
        $stmt = $this->pdo->query("SELECT * FROM ordine ORDER BY data_ordine desc");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM ordine WHERE id_ordine = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $sql = "INSERT INTO ordine
        (data_ordine, data_consegna_prevista,data_consegna_effettiva,stato_ordine, costo_totale, id_fornitore)
        VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['data_ordine'],
            $data['data_consegna_prevista'],
            $data['data_consegna_effettiva'],
            $data['stato_ordine'],
            $data['costo_totale'],
            $data['id_fornitore'],
        ]);
    }

    public function update($id, $data)
    {
        $sql = "UPDATE ordine SET
            data_ordine = ?, 
            data_consegna_prevista = ?, 
            data_consegna_effettiva = ?, 
            stato_ordine = ?, 
            costo_totale = ?, 
            id_fornitore = ?
            WHERE id_ordine = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['data_ordine'],
            $data['data_consegna_prevista'],
            $data['data_consegna_effettiva'],
            $data['stato_ordine'],
            $data['costo_totale'],
            $data['id_fornitore'],
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM ordine WHERE id_ordine = ?");
        return $stmt->execute([$id]);
    }
}
