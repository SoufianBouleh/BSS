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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <img src="assets/images/logo.png" class="logo" alt="Logo">
        <h1>Login</h1>
        
        <?php if ($errore): ?>
            <div class="alert-error"><?= $errore ?></div>
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
            
            <button type="submit">Accedi</button>
        </form>
        
        <div class="link">
            <a href="registrazione.php">Registrati</a>
        </div>
    </div>
</body>
</html>
