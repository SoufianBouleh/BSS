<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/richiesta.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'dipendente') {
    header('Location: ../../login.php');
    exit;
}

$richiesta = new Richiesta($pdo);


if (isset($_GET['delete'])) {
    $richiesta->delete((int)$_GET['delete']);
    header('Location: index.php');
    exit;
}

$richieste = $richiesta->all();


?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Richieste</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">

    <div class="dashboard-sidebar">
        <h2>Dipendente</h2>
     <a href="dashboard.php">Home</a>
        <a href="../articoli/index.php" >Catalogo</a>
        <a href="richieste/index.php" class="active">Le mie richieste</a>
        <a href="../.././logout.php">LOGOUT</a>

    </div>

    <!-- CONTENUTO -->
    <div class="dashboard-content">

        <div class="page-header">
            <h1>Articoli</h1>
            <a href="aggiungi.php" class="btn btn-primary">+ Aggiungi Richiesta</a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Id richiesta</th>
                    <th>Data richiesta</th>
                    <th>Data approvazione</th>
                    <th>Stato</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>

            <?php if (empty($richieste)): ?>
                <tr>
                    <td colspan="5" class="text-center">Nessuna Richiesta presente</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($richieste as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['id_richiesta']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['data_richiesta']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['data_approvazione']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['note']?? "---") ?></td>
                    <td class="actions">
                        <a href="modifica.php?id=<?= $a['id_richiesta'] ?>" class="btn btn-warning">Modifica</a>
                        <a href="index.php?delete=<?= $a['id_richiesta'] ?>"
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
