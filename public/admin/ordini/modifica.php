<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/ordine.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$ordineModel = new Ordine($pdo);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$dati = $ordineModel->trovaConFornitore($id);
if (!$dati) {
    header('Location: index.php');
    exit;
}

$errori = [];
$fornitori = $ordineModel->elencoFornitori();
$isModificabile = ($dati['stato_ordine'] ?? '') === 'rifiutato';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ordineModel->aggiornaBase($id, $_POST);
        header('Location: index.php');
        exit;
    

}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Ordine</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="../../assets/css/pages/admin-ordini.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">
        <?php
$sidebarBase = '../';
$assetPrefix = '../../assets';
$logoutPath = '../../logout.php';
$activeSection = 'ordini';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Modifica ordine #<?= (int)$id ?></h1>
            <a href="index.php" class="btn btn-warning">Torna agli ordini</a>
        </div>

        <?php if (!$isModificabile): ?>
            <div class="alert alert-warning">Modifica consentita solo per ordini rifiutati.</div>
        <?php endif; ?>

        <?php foreach ($errori as $errore): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
        <?php endforeach; ?>

        <form method="post" class="form-crud">
            <div class="grid-2">
                <div>
                    <label for="data_ordine">Data ordine</label>
                    <input id="data_ordine" name="data_ordine" type="date" value="<?= htmlspecialchars($dati['data_ordine'] ?? '') ?>" required <?= $isModificabile ? '' : 'disabled' ?>>
                </div>
                <div>
                    <label for="id_fornitore">Fornitore</label>
                    <select id="id_fornitore" name="id_fornitore" required <?= $isModificabile ? '' : 'disabled' ?>>
                        <?php foreach ($fornitori as $f): ?>
                            <option value="<?= (int)$f['id_fornitore'] ?>" <?= (int)($dati['id_fornitore'] ?? 0) === (int)$f['id_fornitore'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['nome_fornitore']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="data_consegna_prevista">Consegna prevista</label>
                    <input id="data_consegna_prevista" name="data_consegna_prevista" type="date" value="<?= htmlspecialchars($dati['data_consegna_prevista'] ?? '') ?>" <?= $isModificabile ? '' : 'disabled' ?>>
                </div>
                <div>
                    <label>Stato attuale</label>
                    <input type="text" readonly value="<?= htmlspecialchars($dati['stato_ordine'] ?? '') ?>">
                </div>
            </div>
            <?php if ($isModificabile): ?>
                <button type="submit">Salva modifica e reinvia ordine</button>
            <?php endif; ?>
        </form>
    </div>
</div>
</body>
</html>
















