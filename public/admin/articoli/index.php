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
    <link rel="stylesheet" href="../../assets/css/style1.css">
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">

    <div class="dashboard-sidebar">
              <div style="padding: 1.5rem; border-bottom: 1px solid var(--gray-800); display: flex; align-items: center; justify-content: center;">
            <img src="../../assets/images/logo.png" alt="Logo" style="max-width: 120px; height: auto;">
        </div>
        <a href="../dashboard.php" >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            Dashboard
        </a>
        <a href="../articoli/index.php" class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
            Articoli
        </a>
        <a href="../fornitori/index.php" >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <rect x="1" y="3" width="15" height="13"></rect>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                <circle cx="18.5" cy="18.5" r="2.5"></circle>
            </svg>
            Fornitori
        </a>
        <a href="../ordini/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            Ordini
        </a>
        <a href="../dipendenti/index.php"  >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Dipendenti
             <!-- <a href="../scorte/index.php"    style="border-left-color: #dc2626; color: #f87171;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg> Scorte Critiche -->
                   <a href="../../logout.php" style="border-top: 1px solid var(--gray-800); margin-top: auto;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Logout
        </a>

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
