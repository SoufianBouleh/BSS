<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/articolo.php';
require_once __DIR__ . '/../../../app/models/fornitore.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$articoloModel = new Articolo($pdo);
$fornitoreModel = new Fornitore($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$dati = $articoloModel->trova($id);
if (!$dati) {
    header('Location: index.php');
    exit;
}

$fornitori = $fornitoreModel->tutti();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $data['id_fornitore_preferito'] = $data['id_fornitore_preferito'] !== '' ? (int)$data['id_fornitore_preferito'] : null;
    $articoloModel->aggiorna($id, $data);
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Modifica Articolo</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
</head>
<body>
<div class="dashboard-wrapper dashboard-admin">
        <?php
$sidebarBase = '../';
$assetPrefix = '../../assets';
$logoutPath = '../../logout.php';
$activeSection = 'articoli';
include __DIR__ . '/../includes/sidebar.php';
?>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Modifica articolo</h1>
            <a href="index.php" class="btn btn-warning">Torna alla lista</a>
        </div>
        <form method="post" class="form-crud">
            <label>Nome</label>
            <input name="nome_articolo" value="<?= htmlspecialchars($dati['nome_articolo']) ?>" required>

            <label>Prezzo unitario</label>
            <input name="prezzo_unitario" type="number" min="0" step="0.01" value="<?= htmlspecialchars((string)$dati['prezzo_unitario']) ?>" required>

            <label>Unita misura</label>
            <input name="unita_misura" value="<?= htmlspecialchars($dati['unita_misura']) ?>" required>

            <label>Disponibile</label>
            <select name="disponibile">
                <option value="1" <?= (int)$dati['disponibile'] === 1 ? 'selected' : '' ?>>Si</option>
                <option value="0" <?= (int)$dati['disponibile'] === 0 ? 'selected' : '' ?>>No</option>
            </select>

            <label>Quantita in stock</label>
            <input name="quantita_in_stock" type="number" min="0" value="<?= (int)$dati['quantita_in_stock'] ?>" required>

            <label>Punto di riordino</label>
            <input name="punto_riordino" type="number" min="0" value="<?= (int)$dati['punto_riordino'] ?>" required>

            <label>Categoria</label>
            <input name="categoria" value="<?= htmlspecialchars((string)$dati['categoria']) ?>">

            <label>Fornitore preferito</label>
            <select name="id_fornitore_preferito">
                <option value="">Nessuno</option>
                <?php foreach ($fornitori as $f): ?>
                    <option value="<?= (int)$f['id_fornitore'] ?>" <?= (int)$dati['id_fornitore_preferito'] === (int)$f['id_fornitore'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['nome_fornitore']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Descrizione</label>
            <textarea name="descrizione"><?= htmlspecialchars((string)$dati['descrizione']) ?></textarea>

            <button type="submit">Aggiorna articolo</button>
        </form>
    </div>
</div>
</body>
</html>












