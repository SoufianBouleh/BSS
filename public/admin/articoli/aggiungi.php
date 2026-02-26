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
$fornitori = $fornitoreModel->tutti();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $data['id_fornitore_preferito'] = $data['id_fornitore_preferito'] !== '' ? (int)$data['id_fornitore_preferito'] : null;
    $articoloModel->crea($data);
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuovo Articolo</title>
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
            <h1>Nuovo articolo</h1>
            <a href="index.php" class="btn btn-warning">Torna alla lista</a>
        </div>
        <form method="post" class="form-crud">
            <label>Nome</label>
            <input name="nome_articolo" required>

            <label>Prezzo unitario</label>
            <input name="prezzo_unitario" type="number" min="0" step="0.01" required>

            <label>Unita misura</label>
            <input name="unita_misura" value="pz" required>

            <label>Disponibile</label>
            <select name="disponibile">
                <option value="1">Si</option>
                <option value="0">No</option>
            </select>

            <label>Quantita in stock</label>
            <input name="quantita_in_stock" type="number" min="0" value="0" required>

            <label>Punto di riordino</label>
            <input name="punto_riordino" type="number" min="0" value="0" required>

            <label>Categoria</label>
            <input name="categoria">

            <label>Fornitore preferito</label>
            <select name="id_fornitore_preferito">
                <option value="">Nessuno</option>
                <?php foreach ($fornitori as $f): ?>
                    <option value="<?= (int)$f['id_fornitore'] ?>"><?= htmlspecialchars($f['nome_fornitore']) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Descrizione</label>
            <textarea name="descrizione"></textarea>

            <button type="submit">Salva articolo</button>
        </form>
    </div>
</div>
</body>
</html>












