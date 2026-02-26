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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$dati = $richiestaModel->trova($id);
if (!$dati || (int)$dati['id_dipendente'] !== $idDipendente || !in_array($dati['stato'], ['in_attesa', 'respinta'], true)) {
    header('Location: index.php');
    exit;
}

$errore = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ok = $richiestaModel->aggiornaDaDipendente(
        $id,
        $idDipendente,
        $_POST['note'] ?? '',
        (int)($_POST['id_articolo'] ?? 0),
        (int)($_POST['quantita_richiesta'] ?? 1),
        $_POST['descrizione_riga'] ?? '',
        isset($_POST['urgente']) ? 1 : 0
    );
    if ($ok) {
        header('Location: index.php');
        exit;
    }
    $errore = 'Aggiornamento non consentito.';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Richiesta</title>
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
            <h1>Modifica richiesta #<?= (int)$id ?></h1>
            <a href="index.php" class="btn btn-warning">Torna alla lista</a>
        </div>
        <?php if ($errore): ?><div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div><?php endif; ?>
        <?php $dettaglio = $dati['dettagli'][0] ?? []; ?>
        <form method="post" class="form-crud">
            <label>Articolo richiesto</label>
            <select name="id_articolo" required>
                <option value="">Seleziona articolo</option>
                <?php foreach ($articoli as $a): ?>
                    <option value="<?= (int)$a['id_articolo'] ?>" <?= (int)($dettaglio['id_articolo'] ?? 0) === (int)$a['id_articolo'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a['nome_articolo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Quantita richiesta</label>
            <input type="number" name="quantita_richiesta" min="1" value="<?= (int)($dettaglio['quantita_richiesta'] ?? 1) ?>" required>

            <label>Descrizione articolo richiesto</label>
            <textarea name="descrizione_riga"><?= htmlspecialchars((string)($dettaglio['descrizione'] ?? '')) ?></textarea>

            <label style="display:flex;gap:.5rem;align-items:center;">
                <input type="checkbox" name="urgente" value="1" style="width:auto;margin:0;" <?= (int)($dettaglio['urgente'] ?? 0) === 1 ? 'checked' : '' ?>>
                Richiesta urgente
            </label>

            <label>Note richiesta</label>
            <textarea name="note" required><?= htmlspecialchars((string)$dati['note']) ?></textarea>
            <button type="submit">Aggiorna richiesta</button>
        </form>
    </div>
</div>
</body>
</html>





