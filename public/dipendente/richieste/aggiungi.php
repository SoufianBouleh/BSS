<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/richiesta.php';

if ($_SESSION['ruolo'] !== 'dipendente') {
    header('Location: ../dashboard.php');
    exit;
}

$richiesta = new Richiesta($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ordine->create($_POST);
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuova Richiesta</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">

<div class="dashboard-content">
    <h1>Nuova Richiesta</h1>

    <form method="POST" class="form-crud">
   <input name="data_ordine" type="date" placeholder="data ordine" required>
        <input name="note" type="number" placeholder="note" required>

        <button type="submit">Salva</button>
    </form>
</div>
</div>
</body>
</html>
