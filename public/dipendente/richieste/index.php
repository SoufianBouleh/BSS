<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/richiesta.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'dipendente') {
    header('Location: ../../login.php');
    exit;
}

$richiestaModel = new Richiesta($pdo);
$idDipendente = $richiestaModel->trovaIdDipendenteDaUtente((int)$_SESSION['user_id']);
if (!$idDipendente) {
    header('Location: ../dashboard.php');
    exit;
}

if (isset($_GET['delete'])) {
    $idDelete = (int)$_GET['delete'];
    $r = $richiestaModel->trova($idDelete);
    if ($r && (int)$r['id_dipendente'] === $idDipendente && in_array($r['stato'], ['in_attesa', 'respinta'], true)) {
        $richiestaModel->elimina($idDelete);
    }
    header('Location: index.php');
    exit;
}

$richieste = $richiestaModel->tuttePerDipendente($idDipendente);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Le Mie Richieste</title>
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
            <h1>Le mie richieste</h1>
            <a href="aggiungi.php" class="btn btn-primary">+ Nuova richiesta</a>
        </div>

        <table class="data-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Data richiesta</th>
                <th>Stato</th>
                <th>Note</th>
                <th>Azioni</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($richieste)): ?>
                <tr><td colspan="5" class="text-center">Nessuna richiesta presente.</td></tr>
            <?php endif; ?>
            <?php foreach ($richieste as $r): ?>
                <tr>
                    <td>#<?= (int)$r['id_richiesta'] ?></td>
                    <td><?= htmlspecialchars($r['data_richiesta'] ?? '---') ?></td>
                    <td><?= htmlspecialchars(str_replace('_', ' ', $r['stato'] ?? '---')) ?></td>
                    <td><?= htmlspecialchars($r['note'] ?: '---') ?></td>
                    <td class="actions">
                        <?php if (in_array($r['stato'], ['in_attesa', 'respinta'], true)): ?>
                            <a href="modifica.php?id=<?= (int)$r['id_richiesta'] ?>" class="btn btn-warning">Modifica</a>
                            <a href="index.php?delete=<?= (int)$r['id_richiesta'] ?>" class="btn btn-danger" onclick="return confirm('Eliminare richiesta?');">Elimina</a>
                        <?php else: ?>
                            <span class="text-muted">Solo visualizzazione</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>





