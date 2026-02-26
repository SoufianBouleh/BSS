<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/articolo.php';
require_once __DIR__ . '/../../../app/models/fornitore.php';
require_once __DIR__ . '/../../../app/models/ordine.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$articoloModel = new Articolo($pdo);
$fornitoreModel = new Fornitore($pdo);
$ordineModel = new Ordine($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$articolo = $articoloModel->trova($id);
if (!$articolo) {
    header('Location: index.php');
    exit;
}

$fornitori = $fornitoreModel->tutti();
$fornitorePreferito = (int)($articolo['id_fornitore_preferito'] ?? 0);
$qtaSuggerita = max(1, ((int)$articolo['punto_riordino'] * 2) - (int)$articolo['quantita_in_stock']);

$errori = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idFornitore = (int)($_POST['id_fornitore'] ?? 0);
    $qta = (int)($_POST['quantita'] ?? 0);
    $dataOrdine = trim((string)($_POST['data_ordine'] ?? ''));
    $dataConsegna = trim((string)($_POST['data_consegna_prevista'] ?? ''));

    if ($idFornitore <= 0) $errori[] = 'Seleziona un fornitore.';
    if ($qta <= 0) $errori[] = 'La quantita deve essere maggiore di zero.';
    if ($dataOrdine === '') $errori[] = 'Data ordine obbligatoria.';
    if ($dataConsegna === '') $errori[] = 'Data consegna prevista obbligatoria.';

    if (empty($errori)) {
        $ordineModel->creaConRighe([
                'data_ordine' => $dataOrdine,
                'data_consegna_prevista' => $dataConsegna,
                'id_fornitore' => $idFornitore
            ], [
                [
                    'id_articolo' => $articolo['id_articolo'],
                    'quantita' => $qta
                ]
            ]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riordina - <?= htmlspecialchars($articolo['nome_articolo']) ?></title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">
        <?php
$sidebarBase = '../';
$assetPrefix = '../../assets';
$logoutPath = '../../logout.php';
$activeSection = 'scorte';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Riordina <?= htmlspecialchars($articolo['nome_articolo']) ?></h1>
            <a href="index.php" class="btn btn-warning">Torna alle scorte</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                Ordine creato con stato <strong>inviato</strong>. Lo stock aumentera solo dopo la conferma dell'ordine.
            </div>
            <a href="../ordini/index.php" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Vai agli ordini
        </a>
        <?php else: ?>
            <?php foreach ($errori as $err): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>

            <form method="post" class="form-crud">
                <label>Articolo</label>
                <input type="text" value="<?= htmlspecialchars($articolo['nome_articolo']) ?>" readonly>

                <label>Stock attuale</label>
                <input type="text" value="<?= (int)$articolo['quantita_in_stock'] ?> <?= htmlspecialchars($articolo['unita_misura'] ?? '') ?>" readonly>

                <label>Punto riordino</label>
                <input type="text" value="<?= (int)$articolo['punto_riordino'] ?>" readonly>

                <label>Fornitore</label>
                <select name="id_fornitore" required>
                    <option value="">Seleziona fornitore</option>
                    <?php foreach ($fornitori as $f): ?>
                        <option value="<?= (int)$f['id_fornitore'] ?>" <?= $fornitorePreferito === (int)$f['id_fornitore'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['nome_fornitore']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Quantita da ordinare</label>
                <input type="number" name="quantita" min="1" value="<?= htmlspecialchars((string)($_POST['quantita'] ?? $qtaSuggerita)) ?>" required>

                <label>Data ordine</label>
                <input type="date" name="data_ordine" value="<?= htmlspecialchars((string)($_POST['data_ordine'] ?? date('Y-m-d'))) ?>" required>

                <label>Data consegna prevista</label>
                <input type="date" name="data_consegna_prevista" value="<?= htmlspecialchars((string)($_POST['data_consegna_prevista'] ?? date('Y-m-d', strtotime('+7 days')))) ?>" required>

                <button type="submit">Crea ordine (inviato)</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
















