<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/Articolo.php';

if ($_SESSION['ruolo'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$articolo = new Articolo($pdo);
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.php');
    exit;
}

$dati = $articolo->find($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $articolo->update($id, $_POST);
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Articolo</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">

<div class="dashboard-content">
    <h1>Modifica Articolo</h1>

    <form method="POST" class="form-crud">
        <input name="nome_articolo" value="<?= $dati['nome_articolo'] ?>" required>
        <input name="prezzo_unitario" type="number" step="0.01" value="<?= $dati['prezzo_unitario'] ?>">
        <input name="unita_misura" value="<?= $dati['unita_misura'] ?>">
        <input name="quantita_in_stock" type="number" value="<?= $dati['quantita_in_stock'] ?>">
        <input name="punto_riordino" type="number" value="<?= $dati['punto_riordino'] ?>">
        <input name="categoria" value="<?= $dati['categoria'] ?>">
        <input name="id_fornitore_preferito" type="number" value="<?= $dati['id_fornitore_preferito'] ?>">
        <textarea name="descrizione"><?= $dati['descrizione'] ?></textarea>

        <select name="disponibile">
            <option value="1" <?= $dati['disponibile'] ? 'selected' : '' ?>>Disponibile</option>
            <option value="0" <?= !$dati['disponibile'] ? 'selected' : '' ?>>Non disponibile</option>
        </select>

        <button type="submit">Aggiorna</button>
    </form>
</div>
</div>
</body>
</html>
