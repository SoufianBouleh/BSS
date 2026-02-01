<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/ordine.php';

if ($_SESSION['ruolo'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$ordine = new Ordine($pdo);
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.php');
    exit;
}

$dati = $ordine->find($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ordine->update($id, $_POST);
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Ordine</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">

<div class="dashboard-content">
    <h1>Modifica Ordine</h1>

    <form method="POST" class="form-crud">
        <input name="data_ordine" value="<?= $dati['data_ordine'] ?>" required>
        <input name="data_consegna_prevista" type="date"  value="<?=$dati['data_consegna_prevista'] ?>">
        <input name="data_consegna_effettiva" type="date" value="<?=$dati['data_consegna_effettiva'] ?>">
        <input name="stato_ordine" type="text" value="<?= $dati['stato_ordine'] ?>"  readonly>
        <input name="costo_totale" type="number" value="<?= $dati['costo_totale'] ?>">
        <input name="id_fornitore"  value="<?= $dati['id_fornitore'] ?>" readonly>

        <button type="submit">Aggiorna</button>
    </form>
</div>
</div>
</body>
</html>
