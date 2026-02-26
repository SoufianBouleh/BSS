<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$fileImpostazioni = __DIR__ . '/../../../app/impostazioni.json';
$default = ['ordini_automatici_scorte' => false];
$impostazioni = $default;
if (file_exists($fileImpostazioni)) {
    $letto = json_decode((string)file_get_contents($fileImpostazioni), true);
    if (is_array($letto)) {
        $impostazioni = array_merge($default, $letto);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $impostazioni['ordini_automatici_scorte'] = isset($_POST['ordini_automatici_scorte']) ? true : false;
    file_put_contents($fileImpostazioni, json_encode($impostazioni, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: index.php?ok=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Impostazioni</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="../../assets/css/pages/impostazioni.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">
        <?php
$sidebarBase = '../';
$assetPrefix = '../../assets';
$logoutPath = '../../logout.php';
$activeSection = 'impostazioni';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Impostazioni</h1>
        </div>

        <?php if (isset($_GET['ok'])): ?>
            <div class="alert alert-success">Impostazioni salvate.</div>
        <?php endif; ?>

        <form method="post" class="settings-box">
            <label style="display:flex;align-items:center;gap:.5rem;">
                <input type="checkbox" name="ordini_automatici_scorte" value="1" <?= !empty($impostazioni['ordini_automatici_scorte']) ? 'checked' : '' ?>>
                Crea ordini automatici quando ci sono scorte critiche
            </label>
            <p class="text-muted mt-1"></p>
            <button type="submit" class="btn btn-primary mt-1">Salva</button>
        </form>

        <div class="cards">
            <a class="card-link" href="articoli.php">
                <h3>Gestione Rapida Articoli</h3>
                <p>Modifica prezzo, disponibilità, punto di riordino e stock in elenco con update.</p>
            </a>
            <a class="card-link" href="utenti.php">
                <h3>Gestione Utenti</h3>
                <p>Aggiorna password utenti (hash automatico) direttamente da tabella.</p>
            </a>
        </div>
    </div>
</div>
</body>
</html>













