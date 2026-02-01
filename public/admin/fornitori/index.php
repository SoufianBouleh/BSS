<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/fornitore.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$fornitore = new Fornitore($pdo);


if (isset($_GET['delete'])) {
    $fornitore->delete((int)$_GET['delete']);
    header('Location: index.php');
    exit;
}

$fornitori = $fornitore->all();


?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Fornitori</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">

    <div class="dashboard-sidebar">
        <h2>Admin</h2>
        <a href="../dashboard.php">Dashboard</a>
        <a href="../articoli/index.php">Articoli</a>
        <a href="../fornitori/index.php" class="active">Fornitori</a>
        <a href="../ordini/index.php">Ordini</a>
         <a href="../dipendenti/index.php">Dipendenti</a>
                  <a href="../.././logout.php">LOGOUT</a>

    </div>

    <!-- CONTENUTO -->
    <div class="dashboard-content">

        <div class="page-header">
            <h1>Articoli</h1>
            <a href="aggiungi.php" class="btn btn-primary">+ Aggiungi Fornitore</a>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CF</th>
                    <th>Email</th>
                    <th>Indirizzo</th>
                    <th>Citta</th>
                    <th>Telefono</th>
                    <th>P.Iva</th>
                    <th>Iban</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>

            <?php if (empty($fornitori)): ?>
                <tr>
                    <td colspan="5" class="text-center">Nessun articolo presente</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($fornitori as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['nome_fornitore']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['cf']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['mail']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['indirizzo']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['citta']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['tel']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['p_iva']?? "---") ?></td>
                    <td><?= htmlspecialchars($a['iban']?? "---") ?></td>
                    <td class="actions">
                        <a href="modifica.php?id=<?= $a['id_fornitore'] ?>" class="btn btn-warning">Modifica</a>
                        <a href="index.php?delete=<?= $a['id_fornitore'] ?>"
                           class="btn btn-danger"
                           onclick="return confirm('Togliere fornitore dalla lista?');">
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
