<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '../../../../app/models/articolo.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'dipendente') {
    header('Location: ../../login.php');
    exit;
}

$articolo = new Articolo($pdo);



$articoli = $articolo->all();


?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Articoli</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">

    <div class="dashboard-sidebar">
        <h2>Admin</h2>
        <a href="../dashboard.php">Dashboard</a>
        <a href="../articoli/index.php" class="active">Articoli</a>
        <a href="../richieste/index.php">Mie Richieste</a>
        <a href="../.././logout.php">LOGOUT</a>

    </div>

    <!-- CONTENUTO -->
    <div class="dashboard-content">

        <div class="page-header">
            <h1>Articoli</h1>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Prezzo</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>

            <?php if (empty($articoli)): ?>
                <tr>
                    <td colspan="5" class="text-center">Nessun articolo presente</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($articoli as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['nome_articolo']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['categoria']?? "---") ?></td>
                    <td>€ <?= number_format($a['prezzo_unitario']?? "---", 2) ?></td>
                    <td><?= htmlspecialchars($a['quantita_in_stock']?? "---") ?></td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>

    </div>
</div>

</body>
</html>
