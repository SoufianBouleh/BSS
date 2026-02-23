<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/ordine.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$ordine = new Ordine($pdo);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$dati = $ordine->findWithFornitore($id);
if (!$dati) {
    header('Location: index.php');
    exit;
}

$fornitori = $ordine->getFornitori();
$errori = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $ordine->updateBase($id, $_POST);
        header('Location: index.php');
        exit;
    } catch (Throwable $e) {
        $errori[] = $e->getMessage();
    }
    $dati = array_merge($dati, $_POST);
}

$isConfermato = ($dati['stato_ordine'] ?? '') === 'confermato';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Ordine</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <style>
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .grid-2 {
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
            <h1>Modifica Ordine #<?= $id ?></h1>
            <div style="display:flex;gap:.5rem;">
                <a href="view_dettagli.php?id=<?= $id ?>" class="btn btn-info">Visualizza dettaglio</a>
                <a href="index.php" class="btn btn-warning">Torna agli ordini</a>
            </div>
        </div>

        <?php if ($isConfermato): ?>
            <div class="alert alert-warning">
                Ordine confermato: modifica non consentita.
            </div>
        <?php endif; ?>

        <?php foreach ($errori as $errore): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
        <?php endforeach; ?>

        <form method="POST" class="form-crud">
            <div class="grid-2">
                <div>
                    <label for="data_ordine">Data ordine</label>
                    <input id="data_ordine" name="data_ordine" type="date" required value="<?= htmlspecialchars($dati['data_ordine'] ?? '') ?>" <?= $isConfermato ? 'disabled' : '' ?>>
                </div>
                <div>
                    <label for="id_fornitore">Fornitore</label>
                    <select id="id_fornitore" name="id_fornitore" required <?= $isConfermato ? 'disabled' : '' ?>>
                        <option value="">Seleziona fornitore</option>
                        <?php foreach ($fornitori as $f): ?>
                            <option value="<?= (int)$f['id_fornitore'] ?>" <?= ((int)($f['id_fornitore']) === (int)($dati['id_fornitore'] ?? 0)) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['nome_fornitore']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="data_consegna_prevista">Data consegna prevista</label>
                    <input id="data_consegna_prevista" name="data_consegna_prevista" type="date" value="<?= htmlspecialchars($dati['data_consegna_prevista'] ?? '') ?>" <?= $isConfermato ? 'disabled' : '' ?>>
                </div>
                <div>
                    <label for="data_consegna_effettiva">Data consegna effettiva</label>
                    <input id="data_consegna_effettiva" name="data_consegna_effettiva" type="date" value="<?= htmlspecialchars($dati['data_consegna_effettiva'] ?? '') ?>" <?= $isConfermato ? 'disabled' : '' ?>>
                </div>
                <div>
                    <label for="stato_ordine">Stato ordine</label>
                    <select id="stato_ordine" name="stato_ordine" <?= $isConfermato ? 'disabled' : '' ?>>
                        <?php
                        $statoSel = $dati['stato_ordine'] ?? 'inviato';
                        foreach (['inviato', 'confermato', 'consegnato', 'annullato', 'rifiutato'] as $stato):
                            ?>
                            <option value="<?= $stato ?>" <?= $statoSel === $stato ? 'selected' : '' ?>><?= ucfirst($stato) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Costo totale</label>
                    <input type="text" value="&euro; <?= number_format((float)($dati['costo_totale'] ?? 0), 2) ?>" readonly>
                </div>
            </div>

            <?php if (!$isConfermato): ?>
                <button type="submit">Aggiorna ordine</button>
            <?php endif; ?>
        </form>
    </div>
</div>

</body>
</html>
