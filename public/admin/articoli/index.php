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
    $articoloModel->delete((int)$_GET['delete']);
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
$nMesiNoRichieste = (int)($_GET['non_richiesti_n_mesi'] ?? 0);

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

if ($nMesiNoRichieste > 0) {
    $sql .= " AND a.id_articolo NOT IN (
        SELECT DISTINCT c3.id_articolo
        FROM contiene c3
        INNER JOIN richiesta r3 ON r3.id_richiesta = c3.id_richiesta
        WHERE r3.data_richiesta >= DATE_SUB(NOW(), INTERVAL " . (int)$nMesiNoRichieste . " MONTH)
    )";
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
        'stock' => $_GET['stock'] ?? '',
        'dip_piu_n' => $_GET['dip_piu_n'] ?? '',
        'non_richiesti_n_mesi' => $_GET['non_richiesti_n_mesi'] ?? ''
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
    <style>
        .filtri-box {
            display:grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap:.75rem;
            background:#fff;
            border:1px solid var(--gray-200);
            border-radius:12px;
            padding:1rem;
            margin-bottom:1rem;
        }
        .filtri-box input,.filtri-box select { width:100%; margin:0; padding:.65rem .75rem; border:1px solid var(--gray-300); border-radius:8px; }
        .alfabeto { display:flex; flex-wrap:wrap; gap:6px; margin-bottom: 1rem; }
        .lettera-link { width:30px; height:30px; display:flex; align-items:center; justify-content:center; border-radius:6px; border:1px solid #d1d5db; background:#f3f4f6; color:#374151; text-decoration:none; font-size:.78rem; font-weight:700; }
        .lettera-link:hover { background:#e5e7eb; }
        .lettera-link.attiva { background:#9a3412; border-color:#9a3412; color:#fff; }
        .lettera-link.tutti { width:auto; padding:0 10px; }
        @media (max-width: 1024px){ .filtri-box { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width: 640px){ .filtri-box { grid-template-columns:1fr; } }
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
        <a href="../articoli/index.php" class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            Articoli
        </a>
        <a href="../fornitori/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            Fornitori
        </a>
        <a href="../ordini/index.php">
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
                <label>Stock</label>
                <select name="stock">
                    <option value="">Tutti</option>
                    <option value="ok" <?= $stock === 'ok' ? 'selected' : '' ?>>Sopra riordino</option>
                    <option value="critico" <?= $stock === 'critico' ? 'selected' : '' ?>>Scorte critiche</option>
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
            <div>
                <label>Articoli non richiesti negli ultimi n mesi</label>
                <input type="number" min="0" name="non_richiesti_n_mesi" value="<?= $nMesiNoRichieste > 0 ? $nMesiNoRichieste : '' ?>">
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






