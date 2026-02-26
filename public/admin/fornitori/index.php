<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/fornitore.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$fornitoreModel = new Fornitore($pdo);

if (isset($_GET['delete'])) {
    $fornitoreModel->elimina((int)$_GET['delete']);
    header('Location: index.php');
    exit;
}

$idFornitoreFiltro = (int)($_GET['id_fornitore'] ?? 0);
$citta = trim((string)($_GET['citta'] ?? ''));
$lettera = strtoupper(trim((string)($_GET['lettera'] ?? '')));
$haMail = trim((string)($_GET['ha_mail'] ?? ''));
$haPiva = trim((string)($_GET['ha_piva'] ?? ''));
$ordina = trim((string)($_GET['ordina'] ?? 'nome_asc'));

$sql = "SELECT * FROM fornitore WHERE 1=1";
$params = [];

if ($idFornitoreFiltro > 0) {
    $sql .= " AND id_fornitore = ?";
    $params[] = $idFornitoreFiltro;
}

if ($citta !== '') {
    $sql .= " AND citta = ?";
    $params[] = $citta;
}

if ($lettera !== '' && preg_match('/^[A-Z]$/', $lettera)) {
    $sql .= " AND UPPER(nome_fornitore) LIKE ?";
    $params[] = $lettera . '%';
}

if ($haMail === '1') {
    $sql .= " AND COALESCE(TRIM(mail), '') <> ''";
} elseif ($haMail === '0') {
    $sql .= " AND COALESCE(TRIM(mail), '') = ''";
}

if ($haPiva === '1') {
    $sql .= " AND COALESCE(TRIM(p_iva), '') <> ''";
} elseif ($haPiva === '0') {
    $sql .= " AND COALESCE(TRIM(p_iva), '') = ''";
}

$orderMap = [
    'nome_asc' => 'nome_fornitore ASC',
    'nome_desc' => 'nome_fornitore DESC',
    'citta_asc' => 'citta ASC, nome_fornitore ASC'
];
$sql .= " ORDER BY " . ($orderMap[$ordina] ?? $orderMap['nome_asc']);

$stmtF = $pdo->prepare($sql);
$stmtF->execute($params);
$fornitori = $stmtF->fetchAll(PDO::FETCH_ASSOC);
$fornitoriTutti = $fornitoreModel->tutti();

$stmtCity = $pdo->query("SELECT DISTINCT citta FROM fornitore WHERE citta IS NOT NULL AND TRIM(citta) <> '' ORDER BY citta ASC");
$cittaList = $stmtCity->fetchAll(PDO::FETCH_COLUMN);

function qf(array $current, array $override): string
{
    $q = array_merge($current, $override);
    foreach ($q as $k => $v) {
        if ($v === null || $v === '') {
            unset($q[$k]);
        }
    }
    return http_build_query($q);
}

$filtroCorrente = [
    'id_fornitore' => $idFornitoreFiltro,
    'citta' => $citta,
    'lettera' => $lettera,
    'ha_mail' => $haMail,
    'ha_piva' => $haPiva,
    'ordina' => $ordina
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Fornitori</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="../../assets/css/pages/admin-liste.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">
    <?php
$sidebarBase = '../';
$assetPrefix = '../../assets';
$logoutPath = '../../logout.php';
$activeSection = 'fornitori';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Fornitori</h1>
            <a href="aggiungi.php" class="btn btn-primary">+ Aggiungi fornitore</a>
        </div>

        <form method="get" class="filters filters-5">
            <select name="id_fornitore">
                <option value="0">Tutti i fornitori</option>
                <?php foreach ($fornitoriTutti as $forn): ?>
                    <option value="<?= (int)$forn['id_fornitore'] ?>" <?= $idFornitoreFiltro === (int)$forn['id_fornitore'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($forn['nome_fornitore']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="citta">
                <option value="">Tutte le citta</option>
                <?php foreach ($cittaList as $city): ?>
                    <option value="<?= htmlspecialchars($city) ?>" <?= $citta === $city ? 'selected' : '' ?>><?= htmlspecialchars($city) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="ha_mail">
                <option value="">Email: tutte</option>
                <option value="1" <?= $haMail === '1' ? 'selected' : '' ?>>Con email</option>
                <option value="0" <?= $haMail === '0' ? 'selected' : '' ?>>Senza email</option>
            </select>
            <select name="ha_piva">
                <option value="">P.IVA: tutte</option>
                <option value="1" <?= $haPiva === '1' ? 'selected' : '' ?>>Con P.IVA</option>
                <option value="0" <?= $haPiva === '0' ? 'selected' : '' ?>>Senza P.IVA</option>
            </select>
            <select name="ordina">
                <option value="nome_asc" <?= $ordina === 'nome_asc' ? 'selected' : '' ?>>Nome A-Z</option>
                <option value="nome_desc" <?= $ordina === 'nome_desc' ? 'selected' : '' ?>>Nome Z-A</option>
                <option value="citta_asc" <?= $ordina === 'citta_asc' ? 'selected' : '' ?>>Citta + Nome</option>
            </select>
            <div style="display:flex;gap:.5rem;">
                <button type="submit" class="btn btn-primary">Filtra</button>
                <a href="index.php" class="btn btn-warning">Reset</a>
            </div>
        </form>

        <div class="alfabeto">
            <a class="lettera-link tutti <?= $lettera === '' ? 'attiva' : '' ?>" href="?<?= qf($filtroCorrente, ['lettera' => null]) ?>">Tutti</a>
            <?php foreach (range('A', 'Z') as $l): ?>
                <a class="lettera-link <?= $lettera === $l ? 'attiva' : '' ?>" href="?<?= qf($filtroCorrente, ['lettera' => $l]) ?>"><?= $l ?></a>
            <?php endforeach; ?>
        </div>

        <table class="data-table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>CF</th>
                <th>Email</th>
                <th>Citta</th>
                <th>Telefono</th>
                <th>P.IVA</th>
                <th>Azioni</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($fornitori)): ?>
                <tr><td colspan="7" class="text-center">Nessun fornitore trovato.</td></tr>
            <?php endif; ?>
            <?php foreach ($fornitori as $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['nome_fornitore'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($f['cf'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($f['mail'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($f['citta'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($f['tel'] ?? '---') ?></td>
                    <td><?= htmlspecialchars($f['p_iva'] ?? '---') ?></td>
                    <td class="actions">
                        <a href="modifica.php?id=<?= (int)$f['id_fornitore'] ?>" class="btn btn-warning">Modifica</a>
                        <a href="index.php?delete=<?= (int)$f['id_fornitore'] ?>" class="btn btn-danger" onclick="return confirm('Eliminare fornitore?');">Elimina</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>














