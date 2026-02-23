<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/articolo.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'dipendente') {
    header('Location: ../../login.php');
    exit;
}

$articolo = new Articolo($pdo);
$articoli = $articolo->all();

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
    <style>
        .filtri-box {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            background: var(--gray-900, #111827);
            border: 1px solid var(--gray-800, #1f2937);
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 15px;
        }

        .filtri-box input,
        .filtri-box select {
            background: var(--gray-800, #1f2937);
            border: 1px solid var(--gray-700, #374151);
            color: #e5e7eb;
            border-radius: 5px;
            padding: 6px 10px;
            font-size: 0.85rem;
        }

        .filtri-box input:focus,
        .filtri-box select:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .filtri-box label {
            font-size: 0.78rem;
            color: #9ca3af;
            font-weight: 600;
            text-transform: uppercase;
        }

        .btn-reset {
            background: transparent;
            border: 1px solid #374151;
            color: #9ca3af;
            border-radius: 5px;
            padding: 6px 12px;
            cursor: pointer;
            font-size: 0.82rem;
        }

        .alfabeto {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 14px;
        }

        .lettera {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            border: 1px solid #374151;
            background: #1f2937;
            color: #9ca3af;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
        }

        .lettera:hover, .lettera.selezionata {
            background: #3b82f6;
            border-color: #3b82f6;
            color: #fff;
        }

        .lettera.tutti {
            width: auto;
            padding: 0 10px;
            font-size: 0.75rem;
        }

        .contatore {
            font-size: 0.82rem;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .contatore b {
            color: #e5e7eb;
        }

        mark {
            background: #fbbf24;
            color: #111;
            border-radius: 2px;
            padding: 0 2px;
        }

        .stock-ok {
            color: #34d399;
        }

        .stock-basso {
            color: #fbbf24;
        }

        .stock-zero {
            color: #f87171;
        }

        #nessun-risultato {
            display: none;
            text-align: center;
            padding: 30px;
            color: #6b7280;
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">
    <div class="dashboard-sidebar">
        <div style="padding:1.5rem;border-bottom:1px solid var(--gray-800);display:flex;align-items:center;justify-content:center;">
            <img src="../../assets/images/logo.png" alt="Logo" style="max-width:120px;height:auto;">
        </div>
        <a href="../dashboard.php">
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
            Catalogo Articoli
        </a>
        <a href="../richieste/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            Mie Richieste
        </a>
        <a href="../../logout.php" style="border-top: 1px solid var(--gray-800); margin-top: auto;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Logout
        </a>
    </div>

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
