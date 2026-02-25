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
            <h1>Aggiorna Password Utenti</h1>
            <a href="index.php" class="btn btn-warning">Torna a impostazioni</a>
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





