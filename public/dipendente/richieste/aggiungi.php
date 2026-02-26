<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/richiesta.php';
require_once __DIR__ . '/../../../app/models/articolo.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'dipendente') {
    header('Location: ../../login.php');
    exit;
}

$richiestaModel = new Richiesta($pdo);
$articoloModel = new Articolo($pdo);
$articoli = array_values(array_filter($articoloModel->tutti(), function ($a) {
    return (int)($a['disponibile'] ?? 1) === 1;
}));
$idDipendente = $richiestaModel->trovaIdDipendenteDaUtente((int)$_SESSION['user_id']);
if (!$idDipendente) {
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $richiestaModel->creaDaDipendente(
        $idDipendente,
        $_POST['note'] ?? '',
        (int)($_POST['id_articolo'] ?? 0),
        (int)($_POST['quantita_richiesta'] ?? 1),
        $_POST['descrizione_riga'] ?? '',
        isset($_POST['urgente']) ? 1 : 0
    );
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuova Richiesta</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">
        <?php
$sidebarBase = '../';
$assetPrefix = '../../assets';
$logoutPath = '../../logout.php';
$activeSection = 'richieste';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Nuova richiesta</h1>
            <a href="index.php" class="btn btn-warning">Torna alla lista</a>
        </div>
        <form method="post" class="form-crud">
            <label>Articolo richiesto</label>
            <select name="id_articolo" required>
                <option value="">Seleziona articolo</option>
                <?php foreach ($articoli as $a): ?>
                    <option value="<?= (int)$a['id_articolo'] ?>"><?= htmlspecialchars($a['nome_articolo']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Quantita richiesta</label>
            <input type="number" name="quantita_richiesta" min="1" value="1" required>

            <label>Descrizione articolo richiesto</label>
            <textarea name="descrizione_riga"></textarea>

            <label style="display:flex;gap:.5rem;align-items:center;">
                <input type="checkbox" name="urgente" value="1" style="width:auto;margin:0;">
                Richiesta urgente
            </label>

            <label>Note richiesta</label>
            <textarea name="note"></textarea>
            <button type="submit">Invia richiesta</button>
        </form>
    </div>
</div>
</body>
</html>





