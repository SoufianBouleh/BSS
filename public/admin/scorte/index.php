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

// Mappa fornitori per id
$fornitoriMap = []; 
foreach ($fornitori as $f) {
    $fornitoriMap[$f['id_fornitore']] = $f;
}

// Filtra scorte critiche
$scorteCritiche = array_filter($articoli, function ($art) {
    return isset($art['quantita_in_stock']) &&
        isset($art['punto_riordino']) &&
        $art['quantita_in_stock'] < $art['punto_riordino'];
});

// Ordina per differenza più grave prima
usort($scorteCritiche, function ($a, $b) {
    $diffA = $a['quantita_in_stock'] - $a['punto_riordino'];
    $diffB = $b['quantita_in_stock'] - $b['punto_riordino'];
    return $diffA - $diffB;
});

$countCritiche = count($scorteCritiche);
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scorte Critiche</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <style>
        .danger-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d0000 100%);
            border-radius: 12px;
            padding: 2rem 2.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            border: 2px solid #dc2626;
            box-shadow: 0 0 30px rgba(220, 38, 38, 0.15);
        }

        .danger-icon {
            font-size: 3.5rem;
            animation: pulse-danger 1.8s ease-in-out infinite;
            flex-shrink: 0;
        }

        @keyframes pulse-danger {

            0%,
            100% {
                transform: scale(1);
                filter: drop-shadow(0 0 6px rgba(220, 38, 38, 0.6));
            }

            50% {
                transform: scale(1.1);
                filter: drop-shadow(0 0 18px rgba(220, 38, 38, 0.9));
            }
        }

        .danger-header h1 {
            color: #fff;
            font-size: 2rem;
            margin: 0 0 0.25rem 0;
            letter-spacing: -0.5px;
        }

        .danger-header p {
            color: #f87171;
            margin: 0;
            font-size: 0.95rem;
        }

        .danger-header .count-badge {
            margin-left: auto;
            background: #dc2626;
            color: white;
            font-size: 2rem;
            font-weight: 800;
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;                                                                                                                                            
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.5);
            flex-shrink: 0;
        }

        /* Override colori tabella per la pagina critica */
        .data-table thead {
            background: linear-gradient(90deg, #1a1a1a 0%, #3b0000 100%);
        }

        .data-table tbody tr.row-critical {
            border-left: 4px solid #dc2626;
        }

        .data-table tbody tr.row-warning {
            border-left: 4px solid #f59e0b;
        }

        .stock-val {
            font-size: 1.2rem;
            font-weight: 800;
        }

        .stock-val.rosso {
            color: #dc2626;
        }

        .stock-val.arancio {
            color: #d97706;
        }

        .diff-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            background: #fee2e2;
            color: #991b1b;
        }

        .riordina-btn {
            background: #dc2626 !important;
            border-color: #dc2626 !important;
            color: white !important;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .riordina-btn:hover {
            background: #b91c1c !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4) !important;
        }

        .fornitore-info {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-top: 0.2rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
            border: 1px solid var(--gray-200);
        }

        .empty-state .ok-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .sidebar-danger a.active {
            border-left-color: #dc2626 !important;
            color: #f87171 !important;
        }
    </style>
</head>

<body>

    <div class="dashboard-wrapper dashboard-admin">

        <div class="dashboard-sidebar">
            <div style="padding: 1.5rem; border-bottom: 1px solid var(--gray-800); display: flex; align-items: center; justify-content: center;">
                <img src="../../assets/images/logo.png" alt="Logo" style="max-width: 120px; height: auto;">
            </div>
            <a href="../dashboard.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>Dashboard
            </a>
            <a href="../articoli/index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>Articoli
            </a>
            <a href="../fornitori/index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg>Fornitori
            </a>
            <a href="../ordini/index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>Ordini
            </a>
            <a href="../dipendenti/index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>Dipendenti
            </a>
            <a href="index.php" class="active" style="border-left-color: #dc2626; color: #f87171;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg> Scorte Critiche

            </a>
            <a href="../../logout.php" style="border-top: 1px solid var(--gray-800); margin-top: auto;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>Logout
            </a>
        </div>

        <div class="dashboard-content">

            <!-- Header pericolo -->
            <div class="danger-header">
                <div class="danger-icon">⚠️</div>
                <div>
                    <h1>Scorte Critiche</h1>
                    <p>
                        <?php if ($countCritiche > 0): ?>
                            <?= $countCritiche ?> articol<?= $countCritiche === 1 ? 'o ha' : 'i hanno' ?> scorte inferiori al punto di riordino. Intervieni subito.
                        <?php else: ?>
                            Nessuna criticità rilevata. Tutti gli articoli sono sopra il punto di riordino.
                        <?php endif; ?>
                    </p>
                </div>
                <?php if ($countCritiche > 0): ?>
                    <div class="count-badge"><?= $countCritiche ?></div>
                <?php endif; ?>
            </div>

            <?php if ($countCritiche === 0): ?>
                <div class="empty-state">
                    <div class="ok-icon">✅</div>
                    <h2 style="margin-bottom: 0.5rem;">Tutto sotto controllo</h2>
                    <p style="color: var(--gray-500);">Nessuna scorta critica al momento.</p>
                    <a href="../dashboard.php" class="btn btn-primary" style="margin-top: 1.5rem;">Torna alla Dashboard</a>
                </div>

            <?php else: ?>

                <!-- <div class="page-header">
            <h1 style="font-size: 1.25rem; margin: 0;">
                Articoli con scorta insufficiente — ordinati per urgenza
            </h1>
            <a href="../ordini/aggiungi.php" class="btn riordina-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12l7-7 7 7"/></svg>
                Nuovo Ordine
            </a>
        </div> -->
                <?php if (isset($_GET['ricevi'])) {

                    $idOrdine = (int) $_GET['ricevi'];

                    try {
                        $pdo->beginTransaction();

                        // 1️⃣ Controllo stato
                        $stmt = $pdo->prepare("SELECT stato_ordine FROM ordine WHERE id_ordine = ?");
                        $stmt->execute([$idOrdine]);
                        $ordineDB = $stmt->fetch();

                        if (!$ordineDB || $ordineDB['stato_ordine'] !== 'inviato') {
                            throw new Exception("Ordine non valido.");
                        }

                        // 2️⃣ Recupero articoli ordine
                        $stmt = $pdo->prepare("
            SELECT id_articolo, quantita
            FROM dettaglio_ordine
            WHERE id_ordine = ?
        ");
                        $stmt->execute([$idOrdine]);
                        $righe = $stmt->fetchAll();

                        // 3️⃣ Aggiorno stock
                        foreach ($righe as $r) {
                            $update = $pdo->prepare("
                UPDATE articolo
                SET quantita_in_stock = quantita_in_stock + ?
                WHERE id_articolo = ?
            ");
                            $update->execute([
                                $r['quantita'],
                                $r['id_articolo']
                            ]);
                        }

                        // 4️⃣ Cambio stato ordine
                        $stmt = $pdo->prepare("
            UPDATE ordine
            SET stato_ordine = 'ricevuto',
                data_consegna_effettiva = NOW()
            WHERE id_ordine = ?
        ");
                        $stmt->execute([$idOrdine]);

                        $pdo->commit();
                    } catch (Exception $e) {
                        $pdo->rollBack();
                    }

                    header('Location: index.php');
                    exit;
                }
                ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="text-align: center;">⚠</th>
                            <th>Articolo</th>
                            <th>Categoria</th>
                            <th>Stock attuale</th>
                            <th>Punto riordino</th>
                            <th>Deficit</th>
                            <th>Fornitore preferito</th>
                            <th>Prezzo unit.</th>
                            <th>Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scorteCritiche as $art):
                            $deficit = $art['punto_riordino'] - $art['quantita_in_stock'];
                            $percentuale = $art['punto_riordino'] > 0 ? ($art['quantita_in_stock'] / $art['punto_riordino']) * 100 : 0;
                            $isRosso = $percentuale < 50;
                            $fornitore = isset($art['id_fornitore_preferito']) ? ($fornitoriMap[$art['id_fornitore_preferito']] ?? null) : null;
                          if (isset($a['stato_ordine']) && $a['stato_ordine'] === 'inviato'): ?>
                            <?php endif;
                            ?>
                            <tr class="<?= $isRosso ? 'row-critical' : 'row-warning' ?> fade-in">
                                <td style="text-align:center; font-size: 1.2rem;">
                                    <?= $isRosso ? '🔴' : '🟠' ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($art['nome_articolo']) ?></strong>
                                    <?php if ($art['descrizione'] ?? ''): ?>
                                        <div class="fornitore-info"><?= htmlspecialchars(mb_strimwidth($art['descrizione'], 0, 50, '…')) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($art['categoria'] ?? '—') ?></td>
                                <td>
                                    <span class="stock-val <?= $isRosso ? 'rosso' : 'arancio' ?>">
                                        <?= $art['quantita_in_stock'] ?>
                                    </span>
                                    <span style="color:var(--gray-400); font-size: 0.8rem;"> <?= htmlspecialchars($art['unita_misura']) ?></span>
                                </td>
                                <td style="font-weight: 600;"><?= $art['punto_riordino'] ?></td>
                                <td>
                                    <span class="diff-badge">-<?= $deficit ?></span>
                                </td>
                                <td>
                                    <?php if ($fornitore): ?>
                                        <strong><?= htmlspecialchars($fornitore['nome_fornitore']) ?></strong>
                                        <div class="fornitore-info"><?= htmlspecialchars($fornitore['mail'] ?? '—') ?></div>
                                    <?php else: ?>
                                        <span style="color:var(--gray-400);">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>€ <?= number_format($art['prezzo_unitario'], 2) ?></td>
                                <td>
                                    <a href="riordina.php?id=<?= $art['id_articolo'] ?>" class="btn riordina-btn" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" />
                                            <line x1="3" y1="6" x2="21" y2="6" />
                                            <path d="M16 10a4 4 0 01-8 0" />
                                        </svg>
                                        Riordina
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php endif; ?>
        </div>
    </div>

</body>

</html>