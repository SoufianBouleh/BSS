<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/richiesta.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'dipendente') {
    header('Location: ../../login.php');
    exit;
}

$richiestaModel = new Richiesta($pdo);
$idDipendente = $richiestaModel->getDipendenteIdByUtente((int)$_SESSION['user_id']);
if (!$idDipendente) {
    header('Location: ../dashboard.php');
    exit;
}

if (isset($_GET['delete'])) {
    $idDelete = (int)$_GET['delete'];
    $r = $richiestaModel->find($idDelete);
    if ($r && (int)$r['id_dipendente'] === $idDipendente && in_array($r['stato'], ['in_attesa', 'respinta'], true)) {
        $richiestaModel->delete($idDelete);
    }
    header('Location: index.php');
    exit;
}

$richieste = $richiestaModel->allByDipendente($idDipendente);
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

