<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/dipendente.php';

if ($_SESSION['ruolo'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$dipendente = new Dipendente($pdo);
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.php');
    exit;
}

$dati = $dipendente->find($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dipendente->update($id, $_POST);
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Dati Dipendente</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">

<div class="dashboard-content">
    <h1>Modifica Dati Dipendente</h1>

    <form method="POST" class="form-crud">
        <input name="nome" value="<?= $dati['nome'] ?>" required>
        <input name="cognome" value="<?= $dati['cognome'] ?>" required>
        <input name="tel" value="<?= $dati['tel'] ?>" required>
        <input name="reparto" value="<?= $dati['reparto'] ?>" required>
        <input name="id_utente" value="<?= $dati['id_utente'] ?>" readonly>
 

        <button type="submit">Aggiorna</button>
    </form>
</div>
</div>
</body>
</html>
