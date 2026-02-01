<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['ruolo'] !== 'admin') {
    header('Location: ../dipendente/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">

    <div class="dashboard-sidebar">
        <h2>Admin</h2>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="./articoli/index.php">Articoli</a>
        <a href="fornitori/index.php">Fornitori</a>
        <a href="ordini/index.php">Ordini</a>
        <a href="dipendenti/index.php">Dipendenti</a>
        <br>
        <a href="../logout.php">LOGOUT</a>
    </div>

    <div class="dashboard-content">
        <h1>Benvenuto, <?= htmlspecialchars($_SESSION['username']) ?></h1>

        <div class="stats-grid">
            <div class="stat-card">📦 Articoli</div>
            <div class="stat-card">🚚 Fornitori</div>
            <div class="stat-card">📑 Ordini</div>
            <div class="stat-card">👥 Dipendenti</div>
        </div>
    </div>

</div>

</body>
</html>
