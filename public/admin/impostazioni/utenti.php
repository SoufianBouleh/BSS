<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id_utente'] ?? 0);
    $password = (string)($_POST['nuova_password'] ?? '');
    if ($id > 0 && $password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE utente SET password_hash = ? WHERE id_utente = ?");
        $stmt->execute([$hash, $id]);
    }
    header('Location: utenti.php?ok=1');
    exit;
}

$utenti = $pdo->query("SELECT id_utente, username, ruolo, email, ultimo_accesso FROM utente ORDER BY id_utente DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Impostazioni Utenti</title>
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
            <h1>Aggiorna Password Utenti</h1>
            <a href="index.php" class="btn btn-warning">Torna alla Home</a>
        </div>
        <?php if (isset($_GET['ok'])): ?><div class="alert alert-success">Password aggiornata.</div><?php endif; ?>
        <table class="data-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Ruolo</th>
                <th>Email</th>
                <th>Ultimo accesso</th>
                <th>Nuova password</th>
                <th>Update</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($utenti as $u): ?>
                <tr>
                    <form method="post">
                        <td><?= (int)$u['id_utente'] ?><input type="hidden" name="id_utente" value="<?= (int)$u['id_utente'] ?>"></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['ruolo']) ?></td>
                        <td><?= htmlspecialchars($u['email'] ?? '---') ?></td>
                        <td><?= htmlspecialchars($u['ultimo_accesso'] ?? '---') ?></td>
                        <td><input type="text" name="nuova_password" minlength="6" required></td>
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










