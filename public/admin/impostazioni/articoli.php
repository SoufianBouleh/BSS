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
    <div class="dashboard-sidebar">
        <div style="padding:1.5rem;border-bottom:1px solid var(--gray-800);display:flex;align-items:center;justify-content:center;">
            <img src="../../assets/images/logo.png" alt="Logo" style="max-width:120px;height:auto;">
        </div>
        <a href="../impostazioni/index.php" class="active">
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
            <h1>Aggiorna Articoli</h1>
            <a href="index.php" class="btn btn-warning">Torna a impostazioni</a>
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





