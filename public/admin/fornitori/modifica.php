<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/fornitore.php';

if ($_SESSION['ruolo'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$fornitore = new Fornitore($pdo);
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.php');
    exit;
}

$dati = $fornitore->find($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fornitore->update($id, $_POST);
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Dati Fornitore</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">

<div class="dashboard-content">
    <h1>Modifica Dtai Fornitore</h1>

    <form method="POST" class="form-crud">
        <input name="nome_fornitore" value="<?= $dati['nome_fornitore'] ?>">
        <input name="cf" type="text" value="<?= $dati['cf'] ?>">
        <input name="mail"  type="email" value="<?= $dati['mail'] ?>">
        <input name="indirizzo" type="text" value="<?= $dati['indirizzo'] ?>">
        <input name="citta" type="text" value="<?= $dati['citta'] ?>">
        <input name="tel" type="text" value="<?= $dati['tel'] ?>">
        <input name="p_va" type="text" value="<?= $dati['p_iva'] ?>">
        <input name="iban" type="text" value="<?= $dati['iban'] ?>">

        <button type="submit">Aggiorna</button>
    </form>
</div>
</div>
</body>
</html>
