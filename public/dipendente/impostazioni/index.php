<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'dipendente') {
    header('Location: ../../login.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT u.username, u.email, u.ruolo, u.ultimo_accesso, d.nome, d.cognome
     FROM utente u
     LEFT JOIN dipendente d ON d.id_utente = u.id_utente
     WHERE u.id_utente = ?"
);
$stmt->execute([(int)$_SESSION['user_id']]);
$profilo = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Impostazioni Account</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="../../assets/css/pages/impostazioni.css">
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
            <h1>Impostazioni account</h1>
        </div>

        <div class="credenziali-card">
            <h3 class="mb-2">Credenziali di accesso</h3>
            <div class="credenziali-grid">
                <div class="credenziale-item">
                    <small>Nome completo</small>
                    <strong><?= htmlspecialchars(trim(($profilo['nome'] ?? '') . ' ' . ($profilo['cognome'] ?? '')) ?: '---') ?></strong>
                </div>
                <div class="credenziale-item">
                    <small>Username</small>
                    <strong><?= htmlspecialchars($profilo['username'] ?? '---') ?></strong>
                </div>
                <div class="credenziale-item">
                    <small>Email</small>
                    <strong><?= htmlspecialchars($profilo['email'] ?? '---') ?></strong>
                </div>
                <div class="credenziale-item">
                    <small>Ruolo</small>
                    <strong><?= htmlspecialchars($profilo['ruolo'] ?? 'dipendente') ?></strong>
                </div>
                <div class="credenziale-item">
                    <small>Ultimo accesso</small>
                    <strong><?= htmlspecialchars($profilo['ultimo_accesso'] ?? '---') ?></strong>
                </div>
                <div class="credenziale-item">
                    <small>Password</small>
                    <strong>Non visibile per sicurezza</strong>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>





