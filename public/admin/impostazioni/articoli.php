<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id_articolo'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE articolo
            SET prezzo_unitario = ?, punto_riordino = ?, quantita_in_stock = ?, disponibile = ?
            WHERE id_articolo = ?");
        $stmt->execute([
            (float)($_POST['prezzo_unitario'] ?? 0),
            (int)($_POST['punto_riordino'] ?? 0),
            (int)($_POST['quantita_in_stock'] ?? 0),
            isset($_POST['disponibile']) ? 1 : 0,
            $id
        ]);
    }
    header('Location: articoli.php?ok=1');
    exit;
}

$articoli = $pdo->query("SELECT id_articolo, nome_articolo, prezzo_unitario, punto_riordino, quantita_in_stock, disponibile FROM articolo ORDER BY nome_articolo")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Impostazioni Articoli</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">
        <?php
$sidebarBase = '../';
$assetPrefix = '../../assets';
$logoutPath = '../../logout.php';
$activeSection = 'impostazioni';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Aggiorna Articoli</h1>
            <a href="index.php" class="btn btn-warning">Torna alla Home</a>
        </div>
        <?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Articolo aggiornato.</div><?php endif; ?>
        <table class="data-table">
            <thead>
            <tr>
                <th>Articolo</th>
                <th>Prezzo</th>
                <th>Punto riordino</th>
                <th>Stock</th>
                <th>Disponibile</th>
                <th>Update</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($articoli as $a): ?>
                <tr>
                    <form method="post">
                        <td>
                            <?= htmlspecialchars($a['nome_articolo']) ?>
                            <input type="hidden" name="id_articolo" value="<?= (int)$a['id_articolo'] ?>">
                        </td>
                        <td><input type="number" step="0.01" min="0" name="prezzo_unitario" value="<?= htmlspecialchars((string)$a['prezzo_unitario']) ?>"></td>
                        <td><input type="number" min="0" name="punto_riordino" value="<?= (int)$a['punto_riordino'] ?>"></td>
                        <td><input type="number" min="0" name="quantita_in_stock" value="<?= (int)$a['quantita_in_stock'] ?>"></td>
                        <td><input type="checkbox" name="disponibile" value="1" <?= (int)$a['disponibile'] === 1 ? 'checked' : '' ?>></td>
                        <td><button class="btn btn-primary" type="submit">Update</button></td>
                    </form>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>










