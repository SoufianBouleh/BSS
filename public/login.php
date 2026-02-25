<?php
session_start();
require_once __DIR__ . '/../app/config.php';

$errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $errore = 'Inserisci username e password';
    } else {
        $stmt = $pdo->prepare("SELECT id_utente, username, password_hash, ruolo FROM utente WHERE username = ?");
        $stmt->execute([$username]);
        $utente = $stmt->fetch();
        
        if ($utente && password_verify($password, $utente['password_hash'])) {
           
                $_SESSION['user_id'] = $utente['id_utente'];
                $_SESSION['username'] = $utente['username'];
                $_SESSION['ruolo'] = $utente['ruolo'];
                
                $stmt = $pdo->prepare("UPDATE utente SET ultimo_accesso = NOW() WHERE id_utente = ?");
                $stmt->execute([$utente['id_utente']]);
                
                if ($utente['ruolo'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: dipendente/dashboard.php');
                }
                exit;
            
        } else {
            $errore = 'Username o password errati';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../public/assets/css/style.css">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-panel">
            <div class="auth-brand">
                <img src="assets/images/logo.png" alt="Logo">
                <h2>BSS</h2>
                <p>Bouleh Supply System<br>Gestione forniture ufficio e cancelleria</p>
            </div>
            <div class="auth-form-side">
                <h1>Bentornato!</h1>
                <p class="auth-subtitle">Accedi al tuo account</p>

                <?php if ($errore): ?>
                    <div class="alert-error"><?= htmlspecialchars($errore) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required autofocus>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>

                    <button type="submit">Accedi al Sistema</button>
                </form>

                <div class="auth-links">
                    <a href="registrazione.php">Registrati</a>
                </div>
            </div>
        </div>
    </div>
    <footer class="app-footer">BSS &bull; Instagram: @bss.office &bull; LinkedIn: BSS Supply &bull; Email: info@bss.local</footer>
</body>
</html>
