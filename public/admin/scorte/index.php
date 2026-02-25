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

$articoli = $articoloModel->all();
$fornitori = $fornitoreModel->all();
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
    foreach ($scorteCritiche as $a) {
        $idArticolo = (int)$a['id_articolo'];
        $idFornitore = (int)($a['id_fornitore_preferito'] ?? 0);
        if ($idFornitore <= 0) {
            continue;
        }
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM ordine o
            INNER JOIN comprende c ON c.id_ordine = o.id_ordine
            WHERE c.id_articolo = ? AND o.stato_ordine = 'inviato'");
        $stmtCheck->execute([$idArticolo]);
        if ((int)$stmtCheck->fetchColumn() > 0) {
            continue;
        }

        $quantita = max(1, ((int)$a['punto_riordino'] * 2) - (int)$a['quantita_in_stock']);
        try {
            $ordineModel->createWithItems([
                'data_ordine' => date('Y-m-d'),
                'data_consegna_prevista' => date('Y-m-d', strtotime('+7 days')),
                'id_fornitore' => $idFornitore
            ], [[
                'id_articolo' => $idArticolo,
                'quantita' => $quantita
            ]]);
            $autoCreati++;
        } catch (Throwable $e) {
        }
    }
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
    <style>
        .top-strip {
            background: #ffffff;
            border: 1px solid var(--gray-200);
            border-left: 4px solid var(--gray-900);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .top-strip h1 { margin: 0; font-size: 1.4rem; }
        .top-strip p { margin: 0; color: var(--gray-600); }
        .critical-badge {
            background: var(--gray-900);
            color: #fff;
            border-radius: 999px;
            padding: .35rem .8rem;
            font-size: .8rem;
            font-weight: 700;
        }
        .filters {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: .75rem;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .filters select { width:100%; padding:.65rem .75rem; border:1px solid var(--gray-300); border-radius:8px; }
        .filters .btn { align-self:end; }
    </style>
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
            Articoli
        </a>
        <a href="../fornitori/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            Fornitori
        </a>
        <a href="../ordini/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Ordini
        </a>
        <a href="../richieste/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Richieste dipendenti
        </a>
        <a href="../dipendenti/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Dipendenti
        </a>
        <a href="../scorte/index.php" class="active scorte-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            Scorte critiche
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






