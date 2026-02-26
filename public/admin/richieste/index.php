<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/richiesta.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$richiestaModel = new Richiesta($pdo);
$messaggio = '';
$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $azione = $_POST['azione'] ?? '';
    $id = (int)($_POST['id_richiesta'] ?? 0);
    if ($azione === 'bulk_elimina') {
        $ids = $_POST['ids'] ?? [];
        if (empty($ids)) {
            $errore = 'Seleziona almeno una richiesta da eliminare.';
        } else {
            $cont = 0;
            foreach ($ids as $idRichiesta) {
                if ($richiestaModel->elimina((int)$idRichiesta)) {
                    $cont++;
                }
            }
            $messaggio = "Eliminate {$cont} richieste.";
        }
    }
    if ($id > 0 && $azione === 'approvata') {
        $esito = $richiestaModel->approvaECreaOrdineSemplice($id);
        if (!empty($esito['ok'])) {
            $messaggio = "Richiesta #{$id} approvata. Ordine inviato creato: #" . (int)$esito['id_ordine'] . ".";
        } else {
            $errore = (string)($esito['errore'] ?? 'Errore durante approvazione richiesta.');
        }
    } elseif ($id > 0 && in_array($azione, ['respinta', 'evasa'], true)) {
        $richiestaModel->aggiornaStato($id, $azione, $_POST['note'] ?? null);
        $messaggio = "Richiesta #{$id} aggiornata a stato {$azione}.";
    }
}

$filtri = [
    'q' => trim((string)($_GET['q'] ?? '')),
    'stato' => trim((string)($_GET['stato'] ?? '')),
    'id_dipendente' => (int)($_GET['id_dipendente'] ?? 0),
    'dal' => trim((string)($_GET['dal'] ?? '')),
    'al' => trim((string)($_GET['al'] ?? ''))
];

$richieste = $richiestaModel->tuttePerAdmin($filtri);

$dipendenti = $pdo->query("SELECT id_dipendente, nome, cognome FROM dipendente ORDER BY cognome, nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Richieste Dipendenti</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="../../assets/css/pages/admin-richieste.css">
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
            <h1>Richieste dipendenti</h1>
        </div>

        <?php if ($messaggio): ?><div class="alert alert-success"><?= htmlspecialchars($messaggio) ?></div><?php endif; ?>
        <?php if ($errore): ?><div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div><?php endif; ?>

        <form method="get" class="filters">
            <input type="text" name="q" value="<?= htmlspecialchars($filtri['q']) ?>" placeholder="ID, dipendente o note">
            <select name="stato">
                <option value="">Tutti gli stati</option>
                <?php foreach (['in_attesa', 'approvata', 'respinta', 'evasa'] as $st): ?>
                    <option value="<?= $st ?>" <?= $filtri['stato'] === $st ? 'selected' : '' ?>><?= ucfirst(str_replace('_', ' ', $st)) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="id_dipendente">
                <option value="0">Tutti i dipendenti</option>
                <?php foreach ($dipendenti as $d): ?>
                    <option value="<?= (int)$d['id_dipendente'] ?>" <?= $filtri['id_dipendente'] === (int)$d['id_dipendente'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['cognome'] . ' ' . $d['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="dal" value="<?= htmlspecialchars($filtri['dal']) ?>">
            <div style="display:flex;gap:.5rem;">
                <input type="date" name="al" value="<?= htmlspecialchars($filtri['al']) ?>">
                <button type="submit" class="btn btn-primary">Filtra</button>
                <a href="index.php" class="btn btn-warning">Reset</a>
            </div>
        </form>

        <form method="post" id="bulkRichiesteForm">
            <input type="hidden" name="azione" value="bulk_elimina">
            <button type="submit" class="btn btn-danger mb-2" id="bulkDeleteRichieste">Elimina richieste selezionate</button>
        </form>
        <table class="data-table">
            <thead>
            <tr>
                <th><input type="checkbox" id="allRichiesteRows"></th>
                <th>ID</th>
                <th>Dipendente</th>
                <th>Data richiesta</th>
                <th>Stato</th>
                <th>Righe</th>
                <th>Note</th>
                <th>Azioni</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($richieste)): ?>
                <tr><td colspan="8" class="text-center">Nessuna richiesta trovata.</td></tr>
            <?php endif; ?>
            <?php foreach ($richieste as $r): ?>
                <?php $stato = (string)$r['stato']; ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= (int)$r['id_richiesta'] ?>" class="rowRichiestaCheck" form="bulkRichiesteForm"></td>
                    <td>#<?= (int)$r['id_richiesta'] ?></td>
                    <td><?= htmlspecialchars(($r['cognome'] ?? '') . ' ' . ($r['nome'] ?? '')) ?><br><span class="text-muted"><?= htmlspecialchars($r['reparto'] ?? '') ?></span></td>
                    <td><?= htmlspecialchars($r['data_richiesta'] ?? '---') ?></td>
                    <td><span class="status status-<?= htmlspecialchars($stato) ?>"><?= htmlspecialchars(str_replace('_', ' ', $stato)) ?></span></td>
                    <td><?= (int)($r['righe'] ?? 0) ?> articoli</td>
                    <td><?= htmlspecialchars($r['note'] ?: '---') ?></td>
                    <td class="actions">
                        <a href="view.php?id=<?= (int)$r['id_richiesta'] ?>" class="btn btn-info">Vedi</a>
                        <?php if ($stato === 'in_attesa' || $stato === 'respinta'): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_richiesta" value="<?= (int)$r['id_richiesta'] ?>">
                                <input type="hidden" name="azione" value="approvata">
                                <button type="submit" class="btn btn-success">Approva</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_richiesta" value="<?= (int)$r['id_richiesta'] ?>">
                                <input type="hidden" name="azione" value="respinta">
                                <button type="submit" class="btn btn-warning">Respingi</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($stato === 'approvata'): ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_richiesta" value="<?= (int)$r['id_richiesta'] ?>">
                                <input type="hidden" name="azione" value="evasa">
                                <button type="submit" class="btn btn-primary">Segna evasa</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            </table>
    </div>
</div>
<script src="../../assets/js/table-bulk-actions.js"></script>
<script>
window.BSS.initBulkTableActions({
    masterSelector: '#allRichiesteRows',
    rowSelector: '.rowRichiestaCheck',
    submitSelector: '#bulkDeleteRichieste',
    emptyMessage: 'Seleziona almeno una richiesta.',
    confirmMessage: 'Eliminare le richieste selezionate?'
});
</script>
</body>
</html>
















