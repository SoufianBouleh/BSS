<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/dipendente.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$dipendente = new Dipendente($pdo);


if (isset($_GET['delete'])) {
    $dipendente->delete((int)$_GET['delete']);
    header('Location: index.php');
    exit;
}

$dipendenti = $dipendente->all();


?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Dipendenti</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">

    <div class="dashboard-sidebar">
        <h2>Admin</h2>
        <a href="../dashboard.php">Dashboard</a>
        <a href="../articoli/index.php">Articoli</a>
        <a href="../fornitori/index.php">Fornitori</a>
        <a href="../ordini/index.php">Ordini</a>
         <a href="dipendenti/index.php"  class="active">Dipendenti</a>
          <a href="../.././logout.php">LOGOUT</a>
    </div>

    <!-- CONTENUTO -->
    <div class="dashboard-content">

        <div class="page-header">
            <h1>Dipendenti</h1>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Cognome</th>
                    <th>Reparto</th>                  
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>

            <?php if (empty($dipendenti)): ?>
                <tr>
                    <td colspan="5" class="text-center">Nessun articolo presente</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($dipendenti as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['nome']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['cognome']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['reparto']?? "---") ?></td>
                    <td class="actions">
                        <a href="modifica.php?id=<?= $a['id_dipendente'] ?>" class="btn btn-warning">Modifica</a>
                        <a href="index.php?delete=<?= $a['id_dipendente'] ?>"
                           class="btn btn-danger"
                           onclick="return confirm('Togliere questo dipendente?');">
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
