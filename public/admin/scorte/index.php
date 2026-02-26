<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/articolo.php';
require_once __DIR__ . '/../../../app/models/fornitore.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$articoloModel = new Articolo($pdo);
$fornitoreModel = new Fornitore($pdo);

$articoli = $articoloModel->tutti();
$fornitori = $fornitoreModel->tutti();
$fornitoriMap = [];
foreach ($fornitori as $f) {
    $fornitoriMap[(int)$f['id_fornitore']] = $f['nome_fornitore'];
}

$scorteCritiche = array_values(array_filter($articoli, function ($art) {
    return (int)($art['quantita_in_stock'] ?? 0) < (int)($art['punto_riordino'] ?? 0);
}));

usort($scorteCritiche, function ($a, $b) {
    $defA = (int)$a['punto_riordino'] - (int)$a['quantita_in_stock'];
    $defB = (int)$b['punto_riordino'] - (int)$b['quantita_in_stock'];
    return $defB <=> $defA;
});

$fileImpostazioni = __DIR__ . '/../../../app/impostazioni.json';
$impostazioni = ['ordini_automatici_scorte' => false];
if (file_exists($fileImpostazioni)) {
    $tmp = json_decode((string)file_get_contents($fileImpostazioni), true);
    if (is_array($tmp)) {
        $impostazioni = array_merge($impostazioni, $tmp);
    }
}

$autoCreati = 0;
if (!empty($impostazioni['ordini_automatici_scorte'])) {
    require_once __DIR__ . '/../../../app/models/ordine.php';
    $ordineModel = new Ordine($pdo);
    $autoCreati = $ordineModel->creaAutomaticiDaScorteCritiche();
}

$filtroCategoria = trim((string)($_GET['categoria'] ?? ''));
$filtroFornitore = (int)($_GET['fornitore'] ?? 0);

if ($filtroCategoria !== '') {
    $scorteCritiche = array_values(array_filter($scorteCritiche, function ($a) use ($filtroCategoria) {
        return strcasecmp((string)($a['categoria'] ?? ''), $filtroCategoria) === 0;
    }));
}

if ($filtroFornitore > 0) {
    $scorteCritiche = array_values(array_filter($scorteCritiche, function ($a) use ($filtroFornitore) {
        return (int)($a['id_fornitore_preferito'] ?? 0) === $filtroFornitore;
    }));
}

$categorie = [];
foreach ($articoli as $a) {
    $cat = trim((string)($a['categoria'] ?? ''));
    if ($cat !== '') {
        $categorie[$cat] = true;
    }
}
ksort($categorie);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scorte Critiche</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="../../assets/css/pages/admin-scorte.css">
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
        <div class="top-strip">
            <div>
                <h1>Scorte critiche</h1>
                <p>Articoli sotto il punto di riordino, pronti per nuovo ordine.</p>
            </div>
            <span class="critical-badge"><?= count($scorteCritiche) ?> critiche</span>
        </div>
        <?php if ($autoCreati > 0): ?>
            <div class="alert alert-success">Creati automaticamente <?= $autoCreati ?> ordini da scorte critiche.</div>
        <?php endif; ?>

        <form method="get" class="filters">
            <select name="categoria">
                <option value="">Tutte le categorie</option>
                <?php foreach (array_keys($categorie) as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $filtroCategoria === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="fornitore">
                <option value="0">Tutti i fornitori</option>
                <?php foreach ($fornitori as $f): ?>
                    <option value="<?= (int)$f['id_fornitore'] ?>" <?= $filtroFornitore === (int)$f['id_fornitore'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['nome_fornitore']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div style="display:flex;gap:.5rem;">
                <button class="btn btn-primary" type="submit">Filtra</button>
                <a href="index.php" class="btn btn-warning">Reset</a>
            </div>
        </form>

        <table class="data-table">
            <thead>
            <tr>
                <th>Articolo</th>
                <th>Stock</th>
                <th>Punto riordino</th>
                <th>Deficit</th>
                <th>Fornitore</th>
                <th>Azione</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($scorteCritiche)): ?>
                <tr><td colspan="6" class="text-center">Nessuna scorta critica con i filtri selezionati.</td></tr>
            <?php endif; ?>
            <?php foreach ($scorteCritiche as $a): ?>
                <?php $deficit = (int)$a['punto_riordino'] - (int)$a['quantita_in_stock']; ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($a['nome_articolo']) ?></strong><br>
                        <span class="text-muted"><?= htmlspecialchars($a['categoria'] ?? '---') ?></span>
                    </td>
                    <td><?= (int)$a['quantita_in_stock'] ?> <?= htmlspecialchars($a['unita_misura'] ?? '') ?></td>
                    <td><?= (int)$a['punto_riordino'] ?></td>
                    <td><?= $deficit ?></td>
                    <td><?= htmlspecialchars($fornitoriMap[(int)($a['id_fornitore_preferito'] ?? 0)] ?? 'Non impostato') ?></td>
                    <td class="actions">
                        <a href="riordina.php?id=<?= (int)$a['id_articolo'] ?>" class="btn btn-primary">Riordina</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>

















