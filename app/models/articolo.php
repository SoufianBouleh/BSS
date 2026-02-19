<?php

class Articolo
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function all()
    {
        $stmt = $this->pdo->query("SELECT * FROM articolo");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM articolo WHERE id_articolo = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $sql = "INSERT INTO articolo
        (nome_articolo, prezzo_unitario, unita_misura, disponibile, quantita_in_stock, punto_riordino, descrizione, categoria, id_fornitore_preferito)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['nome_articolo'],
            $data['prezzo_unitario'],
            $data['unita_misura'],
            $data['disponibile'],
            $data['quantita_in_stock'],
            $data['punto_riordino'],
            $data['descrizione'],
            $data['categoria'],
            $data['id_fornitore_preferito']
        ]);
    }

    public function update($id, $data)
    {
        $sql = "UPDATE articolo SET
            nome_articolo = ?, 
            prezzo_unitario = ?, 
            unita_misura = ?, 
            disponibile = ?, 
            quantita_in_stock = ?, 
            punto_riordino = ?, 
            descrizione = ?, 
            categoria = ?, 
            id_fornitore_preferito = ?
            WHERE id_articolo = ?";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['nome_articolo'],
            $data['prezzo_unitario'],
            $data['unita_misura'],
            $data['disponibile'],
            $data['quantita_in_stock'],
            $data['punto_riordino'],
            $data['descrizione'],
            $data['categoria'],
            $data['id_fornitore_preferito'],
            $id
        ]);
    }
    

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM articolo WHERE id_articolo = ?");
        return $stmt->execute([$id]);
    }
}
