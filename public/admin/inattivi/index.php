<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/articolo.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$articoloModel = new Articolo($pdo);
$articoliInattivi = $articoloModel->nonOrdinatiDaSeiMesi();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articoli inattivi</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">
    <?php
$sidebarBase = '../';
$assetPrefix = '../../assets';
$logoutPath = '../../logout.php';
$activeSection = 'inattivi';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Articoli inattivi</h1>
        </div>
        <div class="alert alert-warning">Articoli non richiesti negli ultimi 6 mesi.</div>

        <table class="data-table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Prezzo</th>
                <th>Stock</th>
                <th>Punto riordino</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($articoliInattivi)): ?>
                <tr><td colspan="5" class="text-center">Nessun articolo inattivo trovato.</td></tr>
            <?php endif; ?>
            <?php foreach ($articoliInattivi as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['nome_articolo'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($a['categoria'] ?? '---') ?></td>
                    <td>EUR <?= number_format((float)($a['prezzo_unitario'] ?? 0), 2, ',', '.') ?></td>
                    <td><?= (int)($a['quantita_in_stock'] ?? 0) ?></td>
                    <td><?= (int)($a['punto_riordino'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>


