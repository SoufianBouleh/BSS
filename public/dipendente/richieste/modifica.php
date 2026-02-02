<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/richiesta.php';

if ($_SESSION['ruolo'] !== 'dipendente') {
    header('Location: /../../../login.php');
    exit;
}

$richiesta = new Richiesta($pdo);
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: index.php');
    exit;
}

$dati = $richiesta->find($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fornitore->update($id, $_POST);
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Dati Fornitore</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">

<div class="dashboard-content">
    <h1>Modifica Ordine</h1>

    <form method="POST" class="form-crud">

        <button type="submit">Aggiorna</button>
    </form>
</div>
</div>
</body>
</html>
