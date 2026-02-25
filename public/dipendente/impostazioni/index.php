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
    <style>
        .credenziali-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 1.2rem;
            box-shadow: var(--shadow);
        }
        .credenziali-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .9rem;
        }
        .credenziale-item {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: .8rem;
        }
        .credenziale-item small {
            display: block;
            color: var(--gray-500);
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: .2rem;
        }
        .credenziale-item strong {
            color: var(--gray-900);
            word-break: break-word;
        }
        @media (max-width: 768px) {
            .credenziali-grid { grid-template-columns: 1fr; }
        }
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
        <a href="../articoli/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            Catalogo articoli
        </a>
        <a href="../richieste/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Mie richieste
        </a>
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

