<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/articolo.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$articoloModel = new Articolo($pdo);

if (isset($_GET['delete'])) {
    $articoloModel->elimina((int)$_GET['delete']);
    header('Location: index.php');
    exit;
}

$nomeInizia = strtoupper(trim((string)($_GET['nome_inizia'] ?? '')));
$categoria = trim((string)($_GET['categoria'] ?? ''));
$disponibile = trim((string)($_GET['disponibile'] ?? ''));
$prezzoMin = $_GET['prezzo_min'] ?? '';
$prezzoMax = $_GET['prezzo_max'] ?? '';
$stock = trim((string)($_GET['stock'] ?? ''));
$nDipMese = (int)($_GET['dip_piu_n'] ?? 0);

$sql = "SELECT a.*
        FROM articolo a
        WHERE 1=1";
$params = [];

if ($nomeInizia !== '') {
    $sql .= " AND UPPER(a.nome_articolo) LIKE ?";
    $params[] = $nomeInizia . '%';
}

if ($categoria !== '') {
    $sql .= " AND a.categoria = ?";
    $params[] = $categoria;
}

switch ($disponibile) {
    case '1':
    case '0':
        $sql .= " AND a.disponibile = ?";
        $params[] = (int)$disponibile;
        break;
}

if ($prezzoMin !== '') {
    $sql .= " AND a.prezzo_unitario >= ?";
    $params[] = (float)$prezzoMin;
}

if ($prezzoMax !== '') {
    $sql .= " AND a.prezzo_unitario <= ?";
    $params[] = (float)$prezzoMax;
}

switch ($stock) {
    case 'critico':
        $sql .= " AND a.quantita_in_stock < a.punto_riordino";
        break;
    case 'ok':
        $sql .= " AND a.quantita_in_stock >= a.punto_riordino";
        break;
    case 'inattivi':
        $sql .= " AND a.id_articolo NOT IN (
            SELECT DISTINCT c3.id_articolo
            FROM contiene c3
            INNER JOIN richiesta r3 ON r3.id_richiesta = c3.id_richiesta
            WHERE r3.data_richiesta >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        )";
        break;
}

if ($nDipMese > 0) {
    $sql .= " AND a.id_articolo IN (
        SELECT DISTINCT c.id_articolo
        FROM contiene c
        INNER JOIN richiesta r ON r.id_richiesta = c.id_richiesta
        WHERE r.id_dipendente IN (
            SELECT r2.id_dipendente
            FROM richiesta r2
            INNER JOIN contiene c2 ON c2.id_richiesta = r2.id_richiesta
            WHERE r2.data_richiesta >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            GROUP BY r2.id_dipendente
            HAVING SUM(c2.quantita_richiesta) > ?
        )
    )";
    $params[] = $nDipMese;
}

$sql .= " ORDER BY a.nome_articolo ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articoli = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categorie = $pdo->query("SELECT DISTINCT categoria FROM articolo WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);

function qa(array $override = []): string
{
    $base = [
        'nome_inizia' => $_GET['nome_inizia'] ?? '',
        'categoria' => $_GET['categoria'] ?? '',
        'disponibile' => $_GET['disponibile'] ?? '',
        'prezzo_min' => $_GET['prezzo_min'] ?? '',
        'prezzo_max' => $_GET['prezzo_max'] ?? '',
        'dip_piu_n' => $_GET['dip_piu_n'] ?? ''
    ];
    $q = array_merge($base, $override);
    foreach ($q as $k => $v) {
        if ($v === '' || $v === null) {
            unset($q[$k]);
        }
    }
    return http_build_query($q);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Articoli</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="../../assets/css/pages/admin-articoli.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">
    <?php
$sidebarBase = '../';
$assetPrefix = '../../assets';
$logoutPath = '../../logout.php';
$activeSection = 'articoli';
include __DIR__ . '/../includes/sidebar.php';
?>
    <div class="dashboard-content">
        <div class="page-header">
            <h1>Articoli</h1>
            <a href="aggiungi.php" class="btn btn-primary">+ Aggiungi articolo</a>
        </div>

        <form method="get" class="filtri-box">
            <div>
                <label>Categoria</label>
                <select name="categoria">
                    <option value="">Tutte</option>
                    <?php foreach ($categorie as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= $categoria === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Disponibile</label>
                <select name="disponibile">
                    <option value="">Tutti</option>
                    <option value="1" <?= $disponibile === '1' ? 'selected' : '' ?>>Si</option>
                    <option value="0" <?= $disponibile === '0' ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            
            <div>
                <label>Prezzo minimo</label>
                <input type="number" min="0" step="0.01" name="prezzo_min" value="<?= htmlspecialchars((string)$prezzoMin) ?>">
            </div>
            <div>
                <label>Prezzo massimo</label>
                <input type="number" min="0" step="0.01" name="prezzo_max" value="<?= htmlspecialchars((string)$prezzoMax) ?>">
            </div>
            <div>
                <label>Dipendenti con richieste > n (ultimo mese)</label>
                <input type="number" min="0" name="dip_piu_n" value="<?= $nDipMese > 0 ? $nDipMese : '' ?>">
            </div>
            <div style="grid-column:1/-1;display:flex;gap:.5rem;">
                <button type="submit" class="btn btn-primary">Filtra</button>
                <a href="index.php" class="btn btn-warning">Reset</a>
            </div>
        </form>

        <div class="alfabeto">
            <a class="lettera-link tutti <?= $nomeInizia === '' ? 'attiva' : '' ?>" href="?<?= qa(['nome_inizia' => null]) ?>">Tutti</a>
            <?php foreach (range('A', 'Z') as $l): ?>
                <a class="lettera-link <?= $nomeInizia === $l ? 'attiva' : '' ?>" href="?<?= qa(['nome_inizia' => $l]) ?>"><?= $l ?></a>
            <?php endforeach; ?>
        </div>

        <table class="data-table">
            <thead>
            <tr>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Disponibile</th>
                <th>Prezzo</th>
                <th>Stock</th>
                <th>Punto riordino</th>
                <th>Azioni</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($articoli)): ?>
                <tr><td colspan="7" class="text-center">Nessun articolo trovato.</td></tr>
            <?php endif; ?>
            <?php foreach ($articoli as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['nome_articolo']) ?></td>
                    <td><?= htmlspecialchars($a['categoria'] ?? '---') ?></td>
                    <td><?= (int)$a['disponibile'] === 1 ? 'Si' : 'No' ?></td>
                    <td>EUR <?= number_format((float)$a['prezzo_unitario'], 2, ',', '.') ?></td>
                    <td><?= (int)$a['quantita_in_stock'] ?></td>
                    <td><?= (int)$a['punto_riordino'] ?></td>
                    <td class="actions">
                        <a href="modifica.php?id=<?= (int)$a['id_articolo'] ?>" class="btn btn-warning">Modifica</a>
                        <a href="index.php?delete=<?= (int)$a['id_articolo'] ?>" class="btn btn-danger" onclick="return confirm('Eliminare articolo?');">Elimina</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>















