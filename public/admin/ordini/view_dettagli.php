<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/ordine.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$ordineModel = new Ordine($pdo);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$ordine = $ordineModel->findWithFornitore($id);
if (!$ordine) {
    header('Location: index.php');
    exit;
}

$dettagli = $ordineModel->findDettagli($id);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dettaglio Ordine</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <style>
        .summary-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 1.25rem;
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .summary-item b {
            display: block;
            color: var(--gray-500);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0.2rem;
        }

        .summary-item span {
            color: var(--gray-900);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">
    <div class="dashboard-sidebar">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--gray-800); display: flex; align-items: center; justify-content: center;">
            <img src="../../assets/images/logo.png" alt="Logo" style="max-width: 120px; height: auto;">
        </div>
        <a href="../dashboard.php">Dashboard</a>
        <a href="../articoli/index.php">Articoli</a>
        <a href="../fornitori/index.php">Fornitori</a>
        <a href="../ordini/index.php" class="active">Ordini</a>
        <a href="../dipendenti/index.php">Dipendenti</a>
        <a href="../scorte/index.php" style="border-left-color: #dc2626; color: #f87171;">Scorte Critiche</a>
        <a href="../../logout.php" style="border-top: 1px solid var(--gray-800); margin-top: auto;">Logout</a>
    </div>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Dettaglio Ordine #<?= (int)$ordine['id_ordine'] ?></h1>
            <div style="display:flex;gap:.5rem;">
                <?php if (($ordine['stato_ordine'] ?? '') !== 'confermato'): ?>
                    <a href="modifica.php?id=<?= (int)$ordine['id_ordine'] ?>" class="btn btn-warning">Modifica</a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-info">Torna alla lista</a>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-grid">
                <div class="summary-item">
                    <b>Fornitore</b>
                    <span><?= htmlspecialchars($ordine['nome_fornitore'] ?? '---') ?></span>
                </div>
                <div class="summary-item">
                    <b>Data ordine</b>
                    <span><?= htmlspecialchars($ordine['data_ordine'] ?: '---') ?></span>
                </div>
                <div class="summary-item">
                    <b>Stato</b>
                    <span><?= htmlspecialchars($ordine['stato_ordine'] ?: '---') ?></span>
                </div>
                <div class="summary-item">
                    <b>Consegna prevista</b>
                    <span><?= htmlspecialchars($ordine['data_consegna_prevista'] ?: '---') ?></span>
                </div>
                <div class="summary-item">
                    <b>Consegna effettiva</b>
                    <span><?= htmlspecialchars($ordine['data_consegna_effettiva'] ?: '---') ?></span>
                </div>
                <div class="summary-item">
                    <b>Totale ordine</b>
                    <span>&euro; <?= number_format((float)($ordine['costo_totale'] ?? 0), 2) ?></span>
                </div>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Articolo</th>
                    <th>Quantita</th>
                    <th>Prezzo unitario</th>
                    <th>Totale riga</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dettagli)): ?>
                    <tr>
                        <td colspan="4" class="text-center">Nessun dettaglio presente per questo ordine.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($dettagli as $riga): ?>
                    <tr>
                        <td><?= htmlspecialchars($riga['nome_articolo'] ?? '---') ?></td>
                        <td><?= (int)($riga['quantita_ordinata'] ?? 0) ?> <?= htmlspecialchars($riga['unita_misura'] ?? '') ?></td>
                        <td>&euro; <?= number_format((float)($riga['prezzo_unitario'] ?? 0), 2) ?></td>
                        <td>&euro; <?= number_format((float)($riga['totale_riga'] ?? 0), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
