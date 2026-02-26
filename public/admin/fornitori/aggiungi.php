<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/fornitore.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$fornitoreModel = new Fornitore($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fornitoreModel->crea($_POST);
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuovo Fornitore</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">
        <?php
$sidebarBase = '../';
$assetPrefix = '../../assets';
$logoutPath = '../../logout.php';
$activeSection = 'fornitori';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Nuovo fornitore</h1>
            <a href="index.php" class="btn btn-warning">Torna alla lista</a>
        </div>
        <form method="post" class="form-crud">
            <label>Nome fornitore</label>
            <input name="nome_fornitore" required>

            <label>Codice fiscale</label>
            <input name="cf">

            <label>Email</label>
            <input name="mail" type="email">

            <label>Indirizzo</label>
            <input name="indirizzo">

            <label>Citta</label>
            <input name="citta">

            <label>Telefono</label>
            <input name="tel">

            <label>Partita IVA</label>
            <input name="p_iva">

            <label>IBAN</label>
            <input name="iban">

            <button type="submit">Salva fornitore</button>
        </form>
    </div>
</div>
</body>
</html>












