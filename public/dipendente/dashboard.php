<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}


if ($_SESSION['ruolo'] !== 'dipendente') {
    header('Location: ../admin/dashboard.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Dipendente</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">

    <div class="dashboard-sidebar">
        <h2>User</h2>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="../dipendente/articoli/index.php">Articoli</a>
        <a href="ordini/index.php">Richieste</a>
        <br>
        <a href="../logout.php">LOGOUT</a>
    </div>

    <div class="dashboard-content">
        <h1>Benvenuto, <?= htmlspecialchars($_SESSION['username']) ?></h1>

        <div class="stats-grid">
            <div class="stat-card">📦 Articoli</div>
            <div class="stat-card">📑 Richieste</div>
        </div>
    </div>

</div>

</body>
</html>

