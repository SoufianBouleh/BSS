<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/articolo.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$articolo = new Articolo($pdo);


if (isset($_GET['delete'])) {
    $articolo->delete((int)$_GET['delete']);
    header('Location: index.php');
    exit;
}

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
        <a href="../fornitori/index.php">Fornitori</a>
        <a href="../ordini/index.php">Ordini</a>
         <a href="../dipendenti/index.php">Dipendenti</a>
                <a href="../.././logout.php">LOGOUT</a>

    </div>

    <!-- CONTENUTO -->
    <div class="dashboard-content">

        <div class="page-header">
            <h1>Articoli</h1>
            <a href="aggiungi.php" class="btn btn-primary">+ Aggiungi articolo</a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Prezzo</th>
                    <th>Stock</th>
                    <th>Azioni</th>
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
                    <td class="actions">
                        <a href="modifica.php?id=<?= $a['id_articolo'] ?>" class="btn btn-warning">Modifica</a>
                        <a href="index.php?delete=<?= $a['id_articolo'] ?>"
                           class="btn btn-danger"
                           onclick="return confirm('Eliminare questo articolo?');">
                           Elimina
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>

    </div>
</div>

</body>
</html>
