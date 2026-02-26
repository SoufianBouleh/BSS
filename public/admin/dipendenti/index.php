<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/dipendente.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$dipendenteModel = new Dipendente($pdo);

if (isset($_GET['delete'])) {
    $dipendenteModel->elimina((int)$_GET['delete']);
    header('Location: index.php');
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
$reparto = trim((string)($_GET['reparto'] ?? ''));

$dipendenti = $dipendenteModel->tutti();
$dipendenti = array_values(array_filter($dipendenti, function ($d) use ($q, $reparto) {
    $okQ = true;
    $okRep = true;
    if ($q !== '') {
        $txt = strtolower(($d['nome'] ?? '') . ' ' . ($d['cognome'] ?? '') . ' ' . ($d['username'] ?? '') . ' ' . ($d['email'] ?? ''));
        $okQ = strpos($txt, strtolower($q)) !== false;
    }
    if ($reparto !== '') {
        $okRep = strcasecmp((string)($d['reparto'] ?? ''), $reparto) === 0;
    }
    return $okQ && $okRep;
}));

$reparti = [];
foreach ($dipendenteModel->tutti() as $d) {
    $rep = trim((string)($d['reparto'] ?? ''));
    if ($rep !== '') $reparti[$rep] = true;
}
ksort($reparti);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Dipendenti</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="../../assets/css/pages/admin-liste.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">
        <?php
$sidebarBase = '../';
$assetPrefix = '../../assets';
$logoutPath = '../../logout.php';
$activeSection = 'dipendenti';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Dipendenti</h1>
        </div>

        <form method="get" class="filters">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Nome, cognome, username o email">
            <select name="reparto">
                <option value="">Tutti i reparti</option>
                <?php foreach (array_keys($reparti) as $rep): ?>
                    <option value="<?= htmlspecialchars($rep) ?>" <?= $reparto === $rep ? 'selected' : '' ?>><?= htmlspecialchars($rep) ?></option>
                <?php endforeach; ?>
            </select>
            <div style="display:flex;gap:.5rem;">
                <button type="submit" class="btn btn-primary">Filtra</button>
                <a href="index.php" class="btn btn-warning">Reset</a>
            </div>
        </form>

        <table class="data-table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Cognome</th>
                <th>Username</th>
                <th>Email</th>
                <th>Telefono</th>
                <th>Reparto</th>
                <th>Azioni</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($dipendenti)): ?>
                <tr><td colspan="7" class="text-center">Nessun dipendente trovato.</td></tr>
            <?php endif; ?>
            <?php foreach ($dipendenti as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['nome'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($d['cognome'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($d['username'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($d['email'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($d['tel'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($d['reparto'] ?? '---') ?></td>
                    <td class="actions">
                        <a href="modifica.php?id=<?= (int)$d['id_dipendente'] ?>" class="btn btn-warning">Modifica Dati Dipendente</a>
                        <a href="index.php?delete=<?= (int)$d['id_dipendente'] ?>" class="btn btn-danger" onclick="return confirm('Togliere dipendente dalla lista?');">Togliere Dipendente</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>














