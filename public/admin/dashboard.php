<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['ruolo'] !== 'admin') {
    header('Location: ../dipendente/dashboard.php');
    exit;
}

// Connessione al database per recuperare dati
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/models/articolo.php';
require_once __DIR__ . '/../../app/models/fornitore.php';
require_once __DIR__ . '/../../app/models/ordine.php';
require_once __DIR__ . '/../../app/models/dipendente.php';

$articoloModel = new Articolo($pdo);
$fornitoreModel = new Fornitore($pdo);
$ordineModel = new Ordine($pdo);
$dipendenteModel = new Dipendente($pdo);

// Recupera tutti gli articoli
$articoli = $articoloModel->all();
$fornitori = $fornitoreModel->all();
$ordini = $ordineModel->all();
$dipendenti = $dipendenteModel->all();

// Calcola scorte critiche (quantità < punto riordino)
$scorteCritiche = array_filter($articoli, function($art) {
    return isset($art['quantita_in_stock']) && 
           isset($art['punto_riordino']) && 
           $art['quantita_in_stock'] < $art['punto_riordino'];
});

// Conta gli elementi
$countArticoli = count($articoli);
$countFornitori = count($fornitori);
$countOrdini = count($ordini);
$countDipendenti = count($dipendenti);
$countScorteCritiche = count($scorteCritiche);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/css/style1.css">
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">

    <div class="dashboard-sidebar">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--gray-800); display: flex; align-items: center; justify-content: center;">
            <img src="../assets/images/logo.png" alt="Logo" style="max-width: 120px; height: auto;">
        </div>
        <a href="dashboard.php" class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            Dashboard
        </a>
        <a href="./articoli/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
            Articoli
        </a>
        <a href="fornitori/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <rect x="1" y="3" width="15" height="13"></rect>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                <circle cx="18.5" cy="18.5" r="2.5"></circle>
            </svg>
            Fornitori
        </a>
        <a href="ordini/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            Ordini
        </a>
        <a href="dipendenti/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Dipendenti
        </a>
        <a href="../logout.php" style="border-top: 1px solid var(--gray-800); margin-top: auto;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Logout
        </a>
    </div>

    <div class="dashboard-content">
        <h1>Benvenuto, <?= htmlspecialchars($_SESSION['username']) ?></h1>

        <div class="stats-grid">
            <div class="stat-card" style="border-top: 3px solid #3b82f6;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3>Articoli Totali</h3>
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
                        <h3>Fornitori Attivi</h3>
                        <div class="stat-value" style="color: #8b5cf6;"><?= $countFornitori ?></div>
                    </div>
                    <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 1rem; border-radius: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="3" width="15" height="13"></rect>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                            <circle cx="5.5" cy="18.5" r="2.5"></circle>
                            <circle cx="18.5" cy="18.5" r="2.5"></circle>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="stat-card" style="border-top: 3px solid #10b981;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3>Ordini</h3>
                        <div class="stat-value" style="color: #10b981;"><?= $countOrdini ?></div>
                    </div>
                    <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 1rem; border-radius: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="stat-card" style="border-top: 3px solid #f59e0b;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3>Dipendenti</h3>
                        <div class="stat-value" style="color: #f59e0b;"><?= $countDipendenti ?></div>
                    </div>
                    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 1rem; border-radius: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sezione Scorte Critiche -->
        <?php if ($countScorteCritiche > 0): ?>
        <div class="scorte-critiche-section">
            <div class="scorte-critiche-card">
                <h2>
                    Scorte Critiche
                    <span class="badge badge-danger"><?= $countScorteCritiche ?></span>
                </h2>
                <p style="color: var(--gray-600); margin-bottom: 1.5rem;">
                    I seguenti articoli hanno scorte inferiori al punto di riordino
                </p>
                
                <?php foreach (array_slice($scorteCritiche, 0, 10) as $item): ?>
                <div class="scorte-item fade-in">
                    <div class="scorte-item-name">
                        <strong><?= htmlspecialchars($item['nome_articolo']) ?></strong>
                        <small style="color: var(--gray-500); display: block; font-weight: normal; margin-top: 0.25rem;">
                            Categoria: <?= htmlspecialchars($item['categoria'] ?? 'N/A') ?>
                        </small>
                    </div>
                    <div class="scorte-item-stock">
                        <div>
                            <small style="color: var(--gray-500); display: block; font-size: 0.75rem;">Stock attuale</small>
                            <span class="stock-critical"><?= $item['quantita_in_stock'] ?></span>
                        </div>
                        <div>
                            <small style="color: var(--gray-500); display: block; font-size: 0.75rem;">Punto riordino</small>
                            <span style="font-weight: 600;"><?= $item['punto_riordino'] ?></span>
                        </div>
                        <a href="articoli/modifica.php?id=<?= $item['id_articolo'] ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                            Riordina
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if ($countScorteCritiche > 10): ?>
                <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-200);">
                    <a href="articoli/index.php" class="btn btn-primary">
                        Vedi tutti gli articoli critici (<?= $countScorteCritiche ?>)
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-success">
            <span style="font-size: 1.5rem; filter: grayscale(100%);">✓</span>
            <div>
                <strong>Tutto sotto controllo</strong>
                <p style="margin: 0; color: var(--gray-600);">Nessuna scorta critica. Tutte le giacenze sono sopra il punto di riordino.</p>
            </div>
        </div>
        <?php endif; ?>

    </div>

</div>

</body>
</html>