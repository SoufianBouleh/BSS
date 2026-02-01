<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/Articolo.php';

if ($_SESSION['ruolo'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$articolo = new Articolo($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $articolo->create($_POST);
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuovo Articolo</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">

<div class="dashboard-content">
    <h1>Nuovo Articolo</h1>

    <form method="POST" class="form-crud">
        <input name="nome_articolo" placeholder="Nome" required>
        <input name="prezzo_unitario" type="number" step="0.01" placeholder="Prezzo" required>
        <input name="unita_misura" placeholder="Unità misura">
        <input name="quantita_in_stock" type="number" placeholder="Quantità">
        <input name="punto_riordino" type="number" placeholder="Punto riordino">
        <input name="categoria" placeholder="Categoria">
        <input name="id_fornitore_preferito" type="number" placeholder="ID Fornitore">
        <textarea name="descrizione" placeholder="Descrizione"></textarea>

        <select name="disponibile">
            <option value="1">Disponibile</option>
            <option value="0">Non disponibile</option>
        </select>

        <button type="submit">Salva</button>
    </form>
</div>
</div>
</body>
</html>
