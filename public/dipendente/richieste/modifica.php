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
$articoli = array_values(array_filter($articoloModel->all(), function ($a) {
    return (int)($a['disponibile'] ?? 1) === 1;
}));
$idDipendente = $richiestaModel->getDipendenteIdByUtente((int)$_SESSION['user_id']);
if (!$idDipendente) {
    header('Location: ../dashboard.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$dati = $richiestaModel->find($id);
if (!$dati || (int)$dati['id_dipendente'] !== $idDipendente || !in_array($dati['stato'], ['in_attesa', 'respinta'], true)) {
    header('Location: index.php');
    exit;
}

$errore = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ok = $richiestaModel->updateByDipendente(
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
    <div class="dashboard-sidebar">
        <div style="padding:1.5rem;border-bottom:1px solid var(--gray-800);display:flex;align-items:center;justify-content:center;">
            <img src="../../assets/images/logo.png" alt="Logo" style="max-width:120px;height:auto;">
        </div>
        <a href="../dashboard.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Dashboard
        </a>
        <a href="../articoli/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            Catalogo articoli
        </a>
        <a href="../richieste/index.php" class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Mie richieste
        </a>
        <a href="../impostazioni/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            Impostazioni
        </a>
        <a href="../../logout.php" style="border-top:1px solid var(--gray-800);margin-top:auto;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Logout
        </a>
    </div>
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

