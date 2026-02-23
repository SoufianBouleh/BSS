<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/ordine.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$ordine = new Ordine($pdo);
$messaggio = '';
$errore = '';

if (isset($_GET['annulla'])) {
    $idAnnulla = (int)$_GET['annulla'];
    if ($ordine->deleteIfInviato($idAnnulla)) {
        $messaggio = "Ordine #{$idAnnulla} annullato con successo.";
    } else {
        $errore = "Ordine #{$idAnnulla} non annullabile (solo stato inviato).";
    }
}

$ordini = $ordine->allWithFornitore();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Ordini</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <style>
        .stato-pill {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .stato-confermato {
            background: #dcfce7;
            color: #166534;
        }

        .stato-inviato {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .stato-annullato {
            background: #fee2e2;
            color: #b91c1c;
        }

        .stato-consegnato {
            background: #e5e7eb;
            color: #374151;
        }

        .stato-rifiutato {
            background: #fef3c7;
            color: #92400e;
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
            </svg>
            Dashboard
        </a>
        <a href="../articoli/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
            Articoli
        </a>
        <a href="../fornitori/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <rect x="1" y="3" width="15" height="13"></rect>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                <circle cx="18.5" cy="18.5" r="2.5"></circle>
            </svg>
            Fornitori
        </a>
        <a href="../ordini/index.php" class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            Ordini
        </a>
        <a href="../dipendenti/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Dipendenti
        </a>
        <a href="../scorte/index.php" style="border-left-color: #dc2626; color: #f87171;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            Scorte Critiche
        </a>
        <a href="../../logout.php" style="border-top: 1px solid var(--gray-800); margin-top: auto;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Logout
        </a>
    </div>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Ordini</h1>
            <a href="aggiungi.php" class="btn btn-primary">+ Aggiungi Ordine</a>
        </div>

        <?php if ($messaggio): ?>
            <div class="alert alert-success"><?= htmlspecialchars($messaggio) ?></div>
        <?php endif; ?>
        <?php if ($errore): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
        <?php endif; ?>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data ordine</th>
                    <th>Consegna prevista</th>
                    <th>Fornitore</th>
                    <th>Stato</th>
                    <th>Totale</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ordini)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Nessun ordine presente</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($ordini as $o):
                    $stato = $o['stato_ordine'] ?? '';
                    $clsStato = 'stato-' . $stato;
                    ?>
                    <tr>
                        <td>#<?= (int)$o['id_ordine'] ?></td>
                        <td><?= htmlspecialchars($o['data_ordine'] ?: '---') ?></td>
                        <td><?= htmlspecialchars($o['data_consegna_prevista'] ?: '---') ?></td>
                        <td><?= htmlspecialchars($o['nome_fornitore'] ?? ('ID ' . (int)$o['id_fornitore'])) ?></td>
                        <td><span class="stato-pill <?= htmlspecialchars($clsStato) ?>"><?= htmlspecialchars($stato) ?></span></td>
                        <td>&euro; <?= number_format((float)($o['costo_totale'] ?? 0), 2) ?></td>
                        <td class="actions">
                            <a href="view_dettagli.php?id=<?= (int)$o['id_ordine'] ?>" class="btn btn-info">Visualizza</a>
                            <?php if ($stato !== 'confermato'): ?>
                                <a href="modifica.php?id=<?= (int)$o['id_ordine'] ?>" class="btn btn-warning">Modifica</a>
                            <?php endif; ?>
                            <?php if ($stato === 'inviato'): ?>
                                <a href="index.php?annulla=<?= (int)$o['id_ordine'] ?>"
                                   class="btn btn-danger"
                                   onclick="return confirm('Annullare ordine #<?= (int)$o['id_ordine'] ?>? Questa azione cancella anche i dettagli.');">
                                    Annulla ordine
                                </a>
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
