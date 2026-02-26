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
                $ok = $ordineModel->eliminaStorico($idOrdine);
                $messaggio = $ok ? "Ordine #{$idOrdine} eliminato dallo storico." : "Ordine non eliminabile.";
                break;
            case 'bulk_elimina':
                $ids = $_POST['ids'] ?? [];
                if (empty($ids)) {
                    $errore = "Seleziona almeno un ordine prima di eliminare.";
                    break;
                }
                $cont = 0;
                foreach ($ids as $id) {
                    if ($ordineModel->eliminaStorico((int)$id)) {
                        $cont++;
                    }
                }
                $messaggio = "Eliminati {$cont} ordini dallo storico.";
                break;
        }
    
}

$filtri = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'stato' => trim((string)($_GET['stato'] ?? '')),
    'id_fornitore' => (int)($_GET['id_fornitore'] ?? 0),
    'dal' => trim((string)($_GET['dal'] ?? '')),
    'al' => trim((string)($_GET['al'] ?? ''))
];
$ordini = $ordineModel->tuttiConFornitore($filtri);
$fornitori = $ordineModel->elencoFornitori();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Ordini</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="../../assets/css/pages/admin-ordini.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">
    <?php
$sidebarBase = '../';
$assetPrefix = '../../assets';
$logoutPath = '../../logout.php';
$activeSection = 'ordini';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Ordini</h1>
            <a href="aggiungi.php" class="btn btn-primary">+ Nuovo ordine</a>
        </div>
        <?php if ($messaggio): ?><div class="alert alert-success"><?= htmlspecialchars($messaggio) ?></div><?php endif; ?>
        <?php if ($errore): ?><div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div><?php endif; ?>

        <form method="get" class="filters">
            <input type="hidden" name="q" value="">
            <select name="id_fornitore">
                <option value="0">Tutti i fornitori</option>
                <?php foreach ($fornitori as $f): ?>
                    <option value="<?= (int)$f['id_fornitore'] ?>" <?= $filtri['id_fornitore'] === (int)$f['id_fornitore'] ? 'selected' : '' ?>><?= htmlspecialchars($f['nome_fornitore']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="stato">
                <option value="">Tutti gli stati</option>
                <?php foreach (['inviato','confermato','rifiutato','annullato','consegnato'] as $st): ?>
                    <option value="<?= $st ?>" <?= $filtri['stato'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="dal" value="<?= htmlspecialchars($filtri['dal']) ?>">
            <div style="display:flex;gap:.5rem;">
                <input type="date" name="al" value="<?= htmlspecialchars($filtri['al']) ?>">
                <button type="submit" class="btn btn-primary">Filtra</button>
                <a href="index.php" class="btn btn-warning">Reset</a>
            </div>
        </form>

        <form method="post" id="formOrdini">
            <input type="hidden" name="azione" id="azioneOrdine" value="">
            <input type="hidden" name="id_ordine" id="idOrdineAzione" value="">
            <!-- file js a parte per eliminazione di più record -->
            <button type="submit" class="btn btn-danger mb-2" id="bulkDeleteBtn">Elimina ordini selezionati</button>
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
                </tr>
                </thead>
                <tbody>
                <?php if (empty($ordini)): ?>
                    <tr><td colspan="7" class="text-center">Nessun ordine trovato.</td></tr>
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
                        <td>
                            <div class="azioni-cell">
                                <div class="azioni-riga">
                                    <span class="azioni-label">Azioni amministrativo</span>
                                    <div class="actions">
                                        <a href="view_dettagli.php?id=<?= (int)$o['id_ordine'] ?>" class="btn btn-info">Vedi</a>
                                        <?php if ($stato === 'inviato'): ?>
                                            <button type="submit"
                                                    class="btn btn-danger js-ordine-action"
                                                    data-id="<?= (int)$o['id_ordine'] ?>"
                                                    data-azione="annulla"
                                                    data-confirm="Annullare ordine #<?= (int)$o['id_ordine'] ?>?">Annulla</button>
                                        <?php elseif ($stato === 'rifiutato'): ?>
                                            <a href="modifica.php?id=<?= (int)$o['id_ordine'] ?>" class="btn btn-warning">Modifica</a>
                                            <button type="submit"
                                                    class="btn btn-danger js-ordine-action"
                                                    data-id="<?= (int)$o['id_ordine'] ?>"
                                                    data-azione="elimina"
                                                    data-confirm="Eliminare ordine #<?= (int)$o['id_ordine'] ?> dallo storico?">Elimina</button>
                                        <?php elseif (in_array($stato, ['confermato','annullato'], true)): ?>
                                            <button type="submit"
                                                    class="btn btn-danger js-ordine-action"
                                                    data-id="<?= (int)$o['id_ordine'] ?>"
                                                    data-azione="elimina"
                                                    data-confirm="Eliminare ordine #<?= (int)$o['id_ordine'] ?> dallo storico?">Elimina</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="azioni-riga">
                                    <span class="azioni-label">Azioni fornitore esterno</span>
                                    <div class="actions">
                                        <?php if ($stato === 'inviato'): ?>
                                            <button type="submit"
                                                    class="btn btn-success js-ordine-action"
                                                    data-id="<?= (int)$o['id_ordine'] ?>"
                                                    data-azione="conferma">Conferma</button>
                                            <button type="submit"
                                                    class="btn btn-warning js-ordine-action"
                                                    data-id="<?= (int)$o['id_ordine'] ?>"
                                                    data-azione="rifiuta">Rifiuta</button>
                                        <?php else: ?>
                                            <span class="text-muted">N/D</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>
</div>
<script src="../../assets/js/table-bulk-actions.js"></script>
<script>
const azioneOrdine = document.getElementById('azioneOrdine');
const idOrdineAzione = document.getElementById('idOrdineAzione');
const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

if (bulkDeleteBtn) {
    bulkDeleteBtn.addEventListener('click', function () {
        azioneOrdine.value = 'bulk_elimina';
        idOrdineAzione.value = '';
    });
}

document.querySelectorAll('.js-ordine-action').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        const msg = btn.getAttribute('data-confirm');
        if (msg && !confirm(msg)) {
            e.preventDefault();
            return;
        }
        azioneOrdine.value = btn.getAttribute('data-azione') || '';
        idOrdineAzione.value = btn.getAttribute('data-id') || '';
    });
});

window.BSS.initBulkTableActions({
    masterSelector: '#allRows',
    rowSelector: '.rowCheck',
    submitSelector: '#bulkDeleteBtn',
    emptyMessage: 'Seleziona almeno un ordine da eliminare.',
    confirmMessage: 'Eliminare i record selezionati dallo storico?'
});
</script>
</body>
</html>

















