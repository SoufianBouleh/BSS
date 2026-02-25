<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/ordine.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$ordineModel = new Ordine($pdo);
$messaggio = '';
$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';
    $idOrdine = (int)($_POST['id_ordine'] ?? 0);

    try {
        switch ($azione) {
            case 'annulla':
                $ok = $ordineModel->annulla($idOrdine);
                $messaggio = $ok ? "Ordine #{$idOrdine} annullato." : "Ordine non annullabile.";
                break;
            case 'conferma':
                $ok = $ordineModel->conferma($idOrdine);
                $messaggio = $ok ? "Ordine #{$idOrdine} confermato e stock aggiornato." : "Ordine non confermabile.";
                break;
            case 'rifiuta':
                $ok = $ordineModel->rifiuta($idOrdine);
                $messaggio = $ok ? "Ordine #{$idOrdine} rifiutato." : "Ordine non rifiutabile.";
                break;
            case 'elimina':
                $ok = $ordineModel->deleteStorico($idOrdine);
                $messaggio = $ok ? "Ordine #{$idOrdine} eliminato dallo storico." : "Ordine non eliminabile.";
                break;
            case 'bulk_elimina':
                $ids = $_POST['ids'] ?? [];
                $cont = 0;
                foreach ($ids as $id) {
                    if ($ordineModel->deleteStorico((int)$id)) {
                        $cont++;
                    }
                }
                $messaggio = "Eliminati {$cont} ordini dallo storico.";
                break;
        }
    } catch (Throwable $e) {
        $errore = $e->getMessage();
    }
}

$filtri = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'stato' => trim((string)($_GET['stato'] ?? '')),
    'id_fornitore' => (int)($_GET['id_fornitore'] ?? 0),
    'dal' => trim((string)($_GET['dal'] ?? '')),
    'al' => trim((string)($_GET['al'] ?? ''))
];
$ordini = $ordineModel->allWithFornitore($filtri);
$fornitori = $ordineModel->getFornitori();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Ordini</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <style>
        .filters{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.75rem;background:#fff;border:1px solid var(--gray-200);border-radius:12px;padding:1rem;margin-bottom:1rem}
        .filters input,.filters select{margin:0;width:100%;padding:.65rem .75rem;border:1px solid var(--gray-300);border-radius:8px}
        .status-pill{display:inline-block;padding:.25rem .6rem;border-radius:999px;font-size:.75rem;font-weight:700;text-transform:uppercase}
        .status-inviato{background:#e5e7eb;color:#374151}.status-confermato{background:#dcfce7;color:#166534}.status-rifiutato{background:#fef3c7;color:#92400e}.status-annullato{background:#fee2e2;color:#991b1b}.status-consegnato{background:#dbeafe;color:#1d4ed8}
        .data-table th:nth-child(7), .data-table td:nth-child(7){min-width:220px}
        .data-table th:nth-child(8), .data-table td:nth-child(8){min-width:250px}
        .data-table th:nth-child(8), .data-table td:nth-child(8){border-left:2px solid #e5e7eb}
        .data-table td:nth-child(8){background:#fafafa}
        .azioni-split{display:flex;flex-direction:column;gap:.55rem}
        .azioni-group{display:flex;flex-wrap:wrap;gap:.5rem}
        .azioni-divider{height:1px;background:#e5e7eb;margin:.1rem 0}
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
        <a href="../ordini/index.php" class="active">
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
        <a href="../scorte/index.php" style="border-left-color:#dc2626;color:#f87171;" class="scorte-link">
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
        <div class="page-header">
            <h1>Ordini</h1>
            <a href="aggiungi.php" class="btn btn-primary">+ Nuovo ordine</a>
        </div>
        <?php if ($messaggio): ?><div class="alert alert-success"><?= htmlspecialchars($messaggio) ?></div><?php endif; ?>
        <?php if ($errore): ?><div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div><?php endif; ?>

        <form method="get" class="filters">
            <input type="text" name="q" value="<?= htmlspecialchars($filtri['q']) ?>" placeholder="ID ordine o fornitore">
            <select name="stato">
                <option value="">Tutti gli stati</option>
                <?php foreach (['inviato','confermato','rifiutato','annullato','consegnato'] as $st): ?>
                    <option value="<?= $st ?>" <?= $filtri['stato'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="id_fornitore">
                <option value="0">Tutti i fornitori</option>
                <?php foreach ($fornitori as $f): ?>
                    <option value="<?= (int)$f['id_fornitore'] ?>" <?= $filtri['id_fornitore'] === (int)$f['id_fornitore'] ? 'selected' : '' ?>><?= htmlspecialchars($f['nome_fornitore']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="dal" value="<?= htmlspecialchars($filtri['dal']) ?>">
            <div style="display:flex;gap:.5rem;">
                <input type="date" name="al" value="<?= htmlspecialchars($filtri['al']) ?>">
                <button type="submit" class="btn btn-primary">Filtra</button>
                <a href="index.php" class="btn btn-warning">Reset</a>
            </div>
        </form>

        <form method="post">
            <input type="hidden" name="azione" value="bulk_elimina">
            <table class="data-table">
                <thead>
                <tr>
                    <th><input type="checkbox" id="allRows"></th>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Fornitore</th>
                    <th>Stato</th>
                    <th>Totale</th>
                    <th>Azioni</th>
                    <th>Azioni da fornitore esterno</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($ordini)): ?>
                    <tr><td colspan="8" class="text-center">Nessun ordine trovato.</td></tr>
                <?php endif; ?>
                <?php foreach ($ordini as $o): ?>
                    <?php $stato = (string)($o['stato_ordine'] ?? ''); ?>
                    <tr>
                        <td>
                            <?php if (in_array($stato, ['confermato','rifiutato','annullato'], true)): ?>
                                <input type="checkbox" name="ids[]" value="<?= (int)$o['id_ordine'] ?>" class="rowCheck">
                            <?php endif; ?>
                        </td>
                        <td>#<?= (int)$o['id_ordine'] ?></td>
                        <td><?= htmlspecialchars($o['data_ordine'] ?: '---') ?></td>
                        <td><?= htmlspecialchars($o['nome_fornitore'] ?? '---') ?></td>
                        <td><span class="status-pill status-<?= htmlspecialchars($stato) ?>"><?= htmlspecialchars($stato) ?></span></td>
                        <td>EUR <?= number_format((float)($o['costo_totale'] ?? 0), 2, ',', '.') ?></td>
                        <td class="actions">
                            <span class="text-muted">N/D</span>
                        </td>
                        <td class="actions">
                            <div class="azioni-split">
                                <div class="azioni-group">
                                    <a href="view_dettagli.php?id=<?= (int)$o['id_ordine'] ?>" class="btn btn-info">Vedi</a>
                                    <?php if ($stato === 'inviato'): ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Annullare ordine #<?= (int)$o['id_ordine'] ?>?');">
                                            <input type="hidden" name="id_ordine" value="<?= (int)$o['id_ordine'] ?>">
                                            <input type="hidden" name="azione" value="annulla">
                                            <button type="submit" class="btn btn-danger">Annulla</button>
                                        </form>
                                    <?php elseif ($stato === 'confermato'): ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Eliminare ordine #<?= (int)$o['id_ordine'] ?> dallo storico?');">
                                            <input type="hidden" name="id_ordine" value="<?= (int)$o['id_ordine'] ?>">
                                            <input type="hidden" name="azione" value="elimina">
                                            <button type="submit" class="btn btn-danger">Elimina</button>
                                        </form>
                                    <?php elseif ($stato === 'rifiutato'): ?>
                                        <a href="modifica.php?id=<?= (int)$o['id_ordine'] ?>" class="btn btn-warning">Modifica</a>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Eliminare ordine #<?= (int)$o['id_ordine'] ?> dallo storico?');">
                                            <input type="hidden" name="id_ordine" value="<?= (int)$o['id_ordine'] ?>">
                                            <input type="hidden" name="azione" value="elimina">
                                            <button type="submit" class="btn btn-danger">Elimina</button>
                                        </form>
                                    <?php elseif ($stato === 'annullato'): ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Eliminare ordine #<?= (int)$o['id_ordine'] ?> dallo storico?');">
                                            <input type="hidden" name="id_ordine" value="<?= (int)$o['id_ordine'] ?>">
                                            <input type="hidden" name="azione" value="elimina">
                                            <button type="submit" class="btn btn-danger">Elimina</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <div class="azioni-divider"></div>
                                <div class="azioni-group">
                                    <?php if ($stato === 'inviato'): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="id_ordine" value="<?= (int)$o['id_ordine'] ?>">
                                            <input type="hidden" name="azione" value="conferma">
                                            <button type="submit" class="btn btn-success">Conferma</button>
                                        </form>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="id_ordine" value="<?= (int)$o['id_ordine'] ?>">
                                            <input type="hidden" name="azione" value="rifiuta">
                                            <button type="submit" class="btn btn-warning">Rifiuta</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">N/D</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-danger mt-2" onclick="return confirm('Eliminare i record selezionati dallo storico?');">Elimina selezionati</button>
        </form>
    </div>
</div>
<script>
document.getElementById('allRows')?.addEventListener('change', function(){
    document.querySelectorAll('.rowCheck').forEach(function(c){ c.checked = !!document.getElementById('allRows').checked; });
});
</script>
</body>
</html>






