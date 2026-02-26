<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/dipendente.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$dipendenteModel = new Dipendente($pdo);
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$dati = $dipendenteModel->trova($id);
if (!$dati) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dipendenteModel->aggiorna($id, $_POST);
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Dipendente</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">
        <?php
$sidebarBase = '../';
$assetPrefix = '../../assets';
$logoutPath = '../../logout.php';
$activeSection = 'dipendenti';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Modifica dipendente</h1>
            <a href="index.php" class="btn btn-warning">Torna alla lista</a>
        </div>
        <form method="post" class="form-crud">
            <label>Nome</label>
            <input name="nome" value="<?= htmlspecialchars((string)$dati['nome']) ?>" required>

            <label>Cognome</label>
            <input name="cognome" value="<?= htmlspecialchars((string)$dati['cognome']) ?>" required>

            <label>Telefono</label>
            <input name="tel" value="<?= htmlspecialchars((string)$dati['tel']) ?>">

            <label>Reparto</label>
            <input name="reparto" value="<?= htmlspecialchars((string)$dati['reparto']) ?>" required>

            <button type="submit">Aggiorna dipendente</button>
        </form>
    </div>
</div>
</body>
</html>












