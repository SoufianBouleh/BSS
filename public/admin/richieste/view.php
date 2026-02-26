<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/richiesta.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$richiestaModel = new Richiesta($pdo);
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$richiesta = $richiestaModel->trovaConDettagli($id);
if (!$richiesta) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dettaglio Richiesta</title>
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
            <h1>Richiesta #<?= (int)$richiesta['id_richiesta'] ?></h1>
            <a href="index.php" class="btn btn-warning">Torna alla lista</a>
        </div>

        <div class="form-crud" style="max-width:100%;">
            <p><strong>Dipendente:</strong> <?= htmlspecialchars(($richiesta['cognome'] ?? '') . ' ' . ($richiesta['nome'] ?? '')) ?> (<?= htmlspecialchars($richiesta['reparto'] ?? '') ?>)</p>
            <p><strong>Data richiesta:</strong> <?= htmlspecialchars($richiesta['data_richiesta'] ?? '---') ?></p>
            <p><strong>Data approvazione:</strong> <?= htmlspecialchars($richiesta['data_approvazione'] ?? '---') ?></p>
            <p><strong>Stato:</strong> <?= htmlspecialchars(str_replace('_', ' ', $richiesta['stato'] ?? '---')) ?></p>
            <p><strong>Note:</strong> <?= htmlspecialchars($richiesta['note'] ?: '---') ?></p>
        </div>

        <table class="data-table mt-2">
            <thead>
            <tr>
                <th>Articolo</th>
                <th>Quantita</th>
                <th>Urgente</th>
                <th>Descrizione</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($richiesta['dettagli'])): ?>
                <tr><td colspan="4" class="text-center">Nessun articolo associato.</td></tr>
            <?php endif; ?>
            <?php foreach ($richiesta['dettagli'] as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['nome_articolo'] ?? '---') ?></td>
                    <td><?= (int)$d['quantita_richiesta'] ?> <?= htmlspecialchars($d['unita_misura'] ?? '') ?></td>
                    <td><?= (int)$d['urgente'] === 1 ? 'Si' : 'No' ?></td>
                    <td><?= htmlspecialchars($d['descrizione'] ?: '---') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>












