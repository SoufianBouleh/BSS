<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['ruolo'] !== 'dipendente') {
    header('Location: ../admin/dashboard.php');
    exit;
}

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/models/articolo.php';
require_once __DIR__ . '/../../app/models/richiesta.php';

$articoloModel = new Articolo($pdo);
$richiestaModel = new Richiesta($pdo);

$articoli = array_values(array_filter($articoloModel->tutti(), function ($a) {
    return (int)($a['disponibile'] ?? 1) === 1;
}));
$idDipendente = $richiestaModel->trovaIdDipendenteDaUtente((int)$_SESSION['user_id']);
$mieRichieste = $idDipendente ? $richiestaModel->tuttePerDipendente($idDipendente) : [];

$richiesteInAttesa = array_filter($mieRichieste, function ($r) {
    $stato = strtolower(trim((string)($r['stato'] ?? '')));
    return strpos($stato, 'attes') !== false || strpos($stato, 'pending') !== false;
});

$countArticoli = count($articoli);
$countMieRichieste = count($mieRichieste);
$countInAttesa = count($richiesteInAttesa);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dipendente</title>
    <link rel="stylesheet" href="../assets/css/style1.css">
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">
        <?php
$sidebarBase = '';
$assetPrefix = '../assets';
$logoutPath = '../logout.php';
$activeSection = 'dashboard';
include __DIR__ . '/includes/sidebar.php';
?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Benvenuto, <?= htmlspecialchars($_SESSION['username']) ?></h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card" style="border-top: 3px solid #3b82f6;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3>Articoli Disponibili</h3>
                        <div class="stat-value" style="color: #3b82f6;"><?= $countArticoli ?></div>
                    </div>
                    <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 1rem; border-radius: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="stat-card" style="border-top: 3px solid #8b5cf6;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3>Le Mie Richieste</h3>
                        <div class="stat-value" style="color: #8b5cf6;"><?= $countMieRichieste ?></div>
                    </div>
                    <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 1rem; border-radius: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="stat-card" style="border-top: 3px solid #f59e0b;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3>Richieste In Attesa</h3>
                        <div class="stat-value" style="color: #f59e0b;"><?= $countInAttesa ?></div>
                    </div>
                    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 1rem; border-radius: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>




