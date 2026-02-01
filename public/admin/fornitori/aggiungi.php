<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/fornitore.php';

if ($_SESSION['ruolo'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$fornitore = new Fornitore($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fornitore->create($_POST);
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuovo Fornitore</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">

<div class="dashboard-content">
    <h1>Nuovo Fornitore</h1>

    <form method="POST" class="form-crud">
        <input name="nome_fornitore" placeholder="Nome Fornitore" required>
        <input name="cf" type="text" placeholder="CF" required>
        <input name="mail"  type="email" placeholder="email">

        <input name="indirizzo" type="text" placeholder="indirizzo" required>
        <input name="citta" type="text" placeholder="citta" required>
        <input name="tel" type="text" placeholder="tel" required>
        <input name="p_va" type="text" placeholder="p_iva" required>
        <input name="iban" type="text" placeholder="iban" required>



        <button type="submit">Salva</button>
    </form>
</div>
</div>
</body>
</html>
