<?php

class Articolo
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function tutti()
    {
        $stmt = $this->pdo->query("SELECT * FROM articolo");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function trova($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM articolo WHERE id_articolo = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crea($data)
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

    public function aggiorna($id, $data)
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
    

    public function elimina($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM articolo WHERE id_articolo = ?");
        return $stmt->execute([$id]);
    }

    public function nonOrdinatiDaSeiMesi()
    {
        $sql = "SELECT a.* FROM articolo a
                WHERE a.id_articolo NOT IN (
                    SELECT DISTINCT c.id_articolo
                    FROM comprende c
                    JOIN ordine o ON c.id_ordine = o.id_ordine
                    WHERE o.data_ordine >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                )";
        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Alias retrocompatibili
    public function all() { return $this->tutti(); }
    public function find($id) { return $this->trova($id); }
    public function create($data) { return $this->crea($data); }
    public function update($id, $data) { return $this->aggiorna($id, $data); }
    public function delete($id) { return $this->elimina($id); }
    public function notOrderedSince6Months() { return $this->nonOrdinatiDaSeiMesi(); }
}
