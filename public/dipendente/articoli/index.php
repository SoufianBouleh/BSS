<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/articolo.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'dipendente') {
    header('Location: ../../login.php');
    exit;
}

$articolo = new Articolo($pdo);
$articoli = array_values(array_filter($articolo->tutti(), function ($a) {
    return (int)($a['disponibile'] ?? 1) === 1;
}));

$categorie = [];
foreach ($articoli as $a) {
    if (!empty($a['categoria']) && !in_array($a['categoria'], $categorie, true)) {
        $categorie[] = $a['categoria'];
    }
}
sort($categorie);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Catalogo Articoli</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <link rel="stylesheet" href="../../assets/css/pages/dipendente-articoli.css">
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
            <h1>Catalogo Articoli</h1>
        </div>

        <div class="filtri-box">
            <div>
                <label>Cerca</label><br>
                <input type="text" id="cerca" placeholder="Nome articolo..." style="min-width:200px;">
            </div>
            <div>
                <label>Categoria</label><br>
                <select id="filtroCategoria">
                    <option value="">Tutte</option>
                    <?php foreach ($categorie as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Prezzo max (&euro;)</label><br>
                <input type="number" id="filtroPrezzo" placeholder="es. 50" style="width:100px;" min="0" step="0.01">
            </div>
            <div>
                <label>Stock</label><br>
                <select id="filtroStock">
                    <option value="">Tutti</option>
                    <option value="ok">Disponibile (&gt;10)</option>
                    <option value="basso">Basso (1-10)</option>
                    <option value="zero">Esaurito (0)</option>
                </select>
            </div>
            <div style="align-self:flex-end;">
                <button class="btn-reset" id="btnReset">Reset</button>
            </div>
        </div>

        <div class="alfabeto" id="alfabeto">
            <div class="lettera tutti selezionata" data-lettera="">Tutti</div>
            <?php foreach (range('A', 'Z') as $l): ?>
                <div class="lettera" data-lettera="<?= $l ?>"><?= $l ?></div>
            <?php endforeach; ?>
            <div class="lettera" data-lettera="#">#</div>
        </div>

        <div class="contatore">Articoli mostrati: <b id="contatore">0</b> / <b><?= count($articoli) ?></b></div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Prezzo</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody id="corpoTabella">
                <?php if (empty($articoli)): ?>
                    <tr>
                        <td colspan="4" class="text-center">Nessun articolo presente</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($articoli as $a):
                    $nome = $a['nome_articolo'] ?? '---';
                    $cat = $a['categoria'] ?? '---';
                    $prezzo = (float)($a['prezzo_unitario'] ?? 0);
                    $stock = (int)($a['quantita_in_stock'] ?? 0);

                    if ($stock === 0) {
                        $cls = 'stock-zero';
                    } elseif ($stock <= 10) {
                        $cls = 'stock-basso';
                    } else {
                        $cls = 'stock-ok';
                    }

                    $prima = strtoupper(substr($nome, 0, 1));
                    if (!ctype_alpha($prima)) {
                        $prima = '#';
                    }
                    ?>
                    <tr
                        data-nome="<?= strtolower(htmlspecialchars($nome)) ?>"
                        data-cat="<?= strtolower(htmlspecialchars($cat)) ?>"
                        data-prezzo="<?= $prezzo ?>"
                        data-stock="<?= $stock ?>"
                        data-lettera="<?= $prima ?>"
                    >
                        <td class="cella-nome"><?= htmlspecialchars($nome) ?></td>
                        <td><?= htmlspecialchars($cat) ?></td>
                        <td>&euro; <?= number_format($prezzo, 2) ?></td>
                        <td class="<?= $cls ?>"><?= $stock === 0 ? 'Esaurito' : $stock ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p id="nessun-risultato">Nessun articolo corrisponde ai filtri scelti.</p>
    </div>
</div>

<script>
    var letteraAttiva = '';

    function evidenzia(cella, termine) {
        if (!cella) return;
        var testo = cella.textContent;
        if (!termine) {
            cella.innerHTML = testo;
            return;
        }
        var regex = new RegExp('(' + termine.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        cella.innerHTML = testo.replace(regex, '<mark>$1</mark>');
    }

    function aggiornaFiltri() {
        var cerca = document.getElementById('cerca').value.toLowerCase().trim();
        var cat = document.getElementById('filtroCategoria').value.toLowerCase();
        var prezzoMax = parseFloat(document.getElementById('filtroPrezzo').value);
        var stock = document.getElementById('filtroStock').value;
        var righe = document.querySelectorAll('#corpoTabella tr[data-nome]');
        var visibili = 0;

        righe.forEach(function (riga) {
            var nome = riga.dataset.nome;
            var rCat = riga.dataset.cat;
            var rPrezzo = parseFloat(riga.dataset.prezzo);
            var rStock = parseInt(riga.dataset.stock, 10);
            var lettera = riga.dataset.lettera;
            var mostra = true;

            if (cerca && nome.indexOf(cerca) === -1) mostra = false;
            if (cat && rCat.indexOf(cat) === -1) mostra = false;
            if (!isNaN(prezzoMax) && rPrezzo > prezzoMax) mostra = false;
            if (stock === 'ok' && rStock <= 10) mostra = false;
            if (stock === 'basso' && (rStock < 1 || rStock > 10)) mostra = false;
            if (stock === 'zero' && rStock !== 0) mostra = false;
            if (letteraAttiva && lettera !== letteraAttiva) mostra = false;

            riga.style.display = mostra ? '' : 'none';

            if (mostra) {
                evidenzia(riga.querySelector('.cella-nome'), cerca);
                visibili++;
            }
        });

        document.getElementById('contatore').textContent = visibili;
        document.getElementById('nessun-risultato').style.display = visibili === 0 ? 'block' : 'none';
    }

    document.getElementById('cerca').addEventListener('input', aggiornaFiltri);
    document.getElementById('filtroCategoria').addEventListener('change', aggiornaFiltri);
    document.getElementById('filtroPrezzo').addEventListener('input', aggiornaFiltri);
    document.getElementById('filtroStock').addEventListener('change', aggiornaFiltri);

    document.getElementById('btnReset').addEventListener('click', function () {
        document.getElementById('cerca').value = '';
        document.getElementById('filtroCategoria').value = '';
        document.getElementById('filtroPrezzo').value = '';
        document.getElementById('filtroStock').value = '';
        letteraAttiva = '';
        document.querySelectorAll('.lettera').forEach(function (b) { b.classList.remove('selezionata'); });
        document.querySelector('.lettera.tutti').classList.add('selezionata');
        aggiornaFiltri();
    });

    document.getElementById('alfabeto').addEventListener('click', function (e) {
        var btn = e.target.closest('.lettera');
        if (!btn) return;
        document.querySelectorAll('.lettera').forEach(function (b) { b.classList.remove('selezionata'); });
        btn.classList.add('selezionata');
        letteraAttiva = btn.dataset.lettera;
        aggiornaFiltri();
    });

    aggiornaFiltri();
</script>

</body>
</html>






