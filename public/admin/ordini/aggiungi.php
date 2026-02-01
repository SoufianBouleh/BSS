<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/ordine.php';

if ($_SESSION['ruolo'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$ordine = new Ordine($pdo);

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
    <title>Nuovo Ordine</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">

<div class="dashboard-content">
    <h1>Nuovo Ordine</h1>

    <form method="POST" class="form-crud">
   <input name="data_ordine" type="date" placeholder="data ordine" required>
        <input name="data_consegna_prevista" type="date"  placeholder="data consegna prevista" required>
        <input name="data_consegna_effettiva" type="date" placeholder="data consegna effettiva" required>
        <input name="stato_ordine" type="text" placeholder="stato ordine" required>
        <input name="costo_totale" type="number" placeholder="costo totale" required>
        <input name="id_fornitore"  type="number"  placeholder="id fornitore" required>

        <button type="submit">Salva</button>
    </form>
</div>
</div>
</body>
</html>
