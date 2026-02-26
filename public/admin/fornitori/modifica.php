<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/fornitore.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$fornitoreModel = new Fornitore($pdo);
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$dati = $fornitoreModel->trova($id);
if (!$dati) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fornitoreModel->aggiorna($id, $_POST);
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Fornitore</title>
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
            <h1>Modifica fornitore</h1>
            <a href="index.php" class="btn btn-warning">Torna alla lista</a>
        </div>
        <form method="post" class="form-crud">
            <label>Nome fornitore</label>
            <input name="nome_fornitore" value="<?= htmlspecialchars((string)$dati['nome_fornitore']) ?>" required>

            <label>Codice fiscale</label>
            <input name="cf" value="<?= htmlspecialchars((string)$dati['cf']) ?>">

            <label>Email</label>
            <input name="mail" type="email" value="<?= htmlspecialchars((string)$dati['mail']) ?>">

            <label>Indirizzo</label>
            <input name="indirizzo" value="<?= htmlspecialchars((string)$dati['indirizzo']) ?>">

            <label>Citta</label>
            <input name="citta" value="<?= htmlspecialchars((string)$dati['citta']) ?>">

            <label>Telefono</label>
            <input name="tel" value="<?= htmlspecialchars((string)$dati['tel']) ?>">

            <label>Partita IVA</label>
            <input name="p_iva" value="<?= htmlspecialchars((string)$dati['p_iva']) ?>">

            <label>IBAN</label>
            <input name="iban" value="<?= htmlspecialchars((string)$dati['iban']) ?>">

            <button type="submit">Aggiorna fornitore</button>
        </form>
    </div>
</div>
</body>
</html>












