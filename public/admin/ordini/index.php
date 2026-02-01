<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/ordine.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$ordine = new Ordine($pdo);


if (isset($_GET['delete'])) {
    $ordine->delete((int)$_GET['delete']);
    header('Location: index.php');
    exit;
}

$ordini = $ordine->all();


?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Ordini</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">

    <div class="dashboard-sidebar">
        <h2>Admin</h2>
        <a href="../dashboard.php">Dashboard</a>
        <a href="../articoli/index.php" >Articoli</a>
        <a href="../fornitori/index.php">Fornitori</a>
        <a href="../ordini/index.php" class="active">Ordini</a>
         <a href="../dipendenti/index.php">Dipendenti</a>
                   <a href="../.././logout.php">LOGOUT</a>


    </div>

    <div class="dashboard-content">

        <div class="page-header">
            <h1>Ordini</h1>
            <a href="aggiungi.php" class="btn btn-primary">+ Aggiungi Ordine</a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data Ordine</th>
                    <th>data_consegna_prevista</th>
                    <th>data_consegna_effettiva</th>
                    <th>stato_ordine</th>
                    <th>costo_totale</th>
                    <th>id fornitore</th>
                    <th>Azioni su Ordini</th>
                </tr>
            </thead>
            <tbody>

            <?php if (empty($ordini)): ?>
                <tr>
                    <td colspan="5" class="text-center">Nessun ordine presente</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($ordini as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['data_ordine']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['data_consegna_prevista'] ?? "---") ?></td>
                    <td><?= htmlspecialchars($a['data_consegna_effettiva']?? "---")  ?></td>
                    <td><?=htmlspecialchars($a['stato_ordine']?? "---") ?></td>
                    <td>€ <?= number_format($a['costo_totale'], 2) ?></td>
                    <td><?=  htmlspecialchars($a['id_fornitore']?? "---")?></td>
                    <td class="actions">
                        <a href="modifica.php?id=<?= $a['id_ordine'] ?>" class="btn btn-warning">Modifica</a>
                        <a href="index.php?delete=<?= $a['id_ordine'] ?>"
                           class="btn btn-danger"
                           onclick="return confirm('Eliminare questo ordine?');">
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
