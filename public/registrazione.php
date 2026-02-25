<?php
session_start();
require_once __DIR__ . '/../app/config.php';

$errore = '';
$successo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $conferma_password = $_POST['conferma_password'];
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $email = trim($_POST['email']);
    $tel = trim($_POST['tel']);
    $reparto = trim($_POST['reparto']);
    
    if (empty($username) || empty($password) || empty($conferma_password) || empty($nome) || empty($cognome) || empty($email) || empty($tel)) {
        $errore = 'Tutti i campi obbligatori devono essere compilati';
    } elseif (strlen($username) < 3) {
        $errore = 'Username deve essere almeno 3 caratteri';
    } elseif (strlen($password) < 6) {
        $errore = 'Password deve essere almeno 6 caratteri';
    } elseif ($password !== $conferma_password) {
        $errore = 'Le password non coincidono';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errore = 'Email non valida';
    } else {
        $stmt = $pdo->prepare("SELECT id_utente FROM utente WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            $errore = 'Username già esistente';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utente (username, password_hash, ruolo,email) VALUES (?, ?, 'dipendente',?)");
            $stmt->execute([$username, $password_hash,$email]);
            $id_utente = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO dipendente (id_utente, nome, cognome, tel, reparto) VALUES (?, ?, ?,  ?, ?)");
            $stmt->execute([$id_utente, $nome, $cognome, $tel, $reparto]);
            
            $successo = 'Richiesta inviata con successo!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-shell">
        <div class="auth-panel">
            <div class="auth-brand">
                <img src="assets/images/logo.png" alt="Logo">
                <h2>BSS</h2>
                <p>Registrazione dipendente<br>Sistema forniture aziendali</p>
            </div>
            <div class="auth-form-side">
                <h1>Nuovo Account</h1>
                <p class="auth-subtitle">Compila i campi per registrarti</p>

                <?php if ($errore): ?>
                    <div class="alert-error"><?= htmlspecialchars($errore) ?></div>
                <?php endif; ?>

                <?php if ($successo): ?>
                    <div class="alert-success"><?= htmlspecialchars($successo) ?></div>
                <?php endif; ?>

                <form method="POST" class="registration-form">
                    <div class="form-grid">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label>Conferma Password *</label>
                        <input type="password" name="conferma_password" required>
                    </div>

                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" name="nome" required value="<?= isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label>Cognome *</label>
                        <input type="text" name="cognome" required value="<?= isset($_POST['cognome']) ? htmlspecialchars($_POST['cognome']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label>Telefono *</label>
                        <input type="text" name="tel" required value="<?= isset($_POST['tel']) ? htmlspecialchars($_POST['tel']) : '' ?>">
                    </div>

                    <div class="form-group">
                        <label>Reparto</label>
                        <input type="text" name="reparto" value="<?= isset($_POST['reparto']) ? htmlspecialchars($_POST['reparto']) : '' ?>">
                    </div>
                    
                    </div>
                    <br>

                    <button type="submit">Invia Richiesta</button>
                </form>

                <div class="auth-links">
                    <a href="login.php">Torna al Login</a>
                </div>
            </div>
        </div>
    </div>
    <footer class="app-footer">BSS &bull; Instagram: @bss.office &bull; LinkedIn: BSS Supply &bull; Email: info@bss.local</footer>
</body>
</html>
