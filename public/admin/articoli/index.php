<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/articolo.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$articolo = new Articolo($pdo);

// elimina articolo
if (isset($_GET['delete'])) {
    $articolo->delete((int)$_GET['delete']);
    header('Location: index.php');
    exit;
}

// --- raccolta parametri di ricerca ---
$cerca      = trim($_GET['cerca'] ?? '');
$catFiltro  = trim($_GET['categoria'] ?? '');
$prezzoMin  = isset($_GET['prezzo_min']) && $_GET['prezzo_min'] !== '' ? (float)$_GET['prezzo_min'] : null;
$prezzoMax  = isset($_GET['prezzo_max']) && $_GET['prezzo_max'] !== '' ? (float)$_GET['prezzo_max'] : null;
$stockFiltro = trim($_GET['stock'] ?? '');
$inattivi   = isset($_GET['inattivi']) && $_GET['inattivi'] == 1;

// --- costruzione query dinamica ---
$sql    = "SELECT * FROM articolo WHERE 1=1";
$params = [];

if ($inattivi) {
    $sql .= " AND id_articolo NOT IN (
                SELECT DISTINCT c.id_articolo
                FROM comprende c
                JOIN ordine o ON c.id_ordine = o.id_ordine
                WHERE o.data_ordine >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              )";
}

if ($cerca !== '') {
    $sql .= " AND nome_articolo LIKE :cerca";
    $params[':cerca'] = '%' . $cerca . '%';
}

if ($catFiltro !== '') {
    $sql .= " AND categoria = :categoria";
    $params[':categoria'] = $catFiltro;
}

if ($prezzoMin !== null) {
    $sql .= " AND prezzo_unitario >= :pmin";
    $params[':pmin'] = $prezzoMin;
}

if ($prezzoMax !== null) {
    $sql .= " AND prezzo_unitario <= :pmax";
    $params[':pmax'] = $prezzoMax;
}

if ($stockFiltro === 'ok') {
    $sql .= " AND quantita_in_stock > 10";
} elseif ($stockFiltro === 'basso') {
    $sql .= " AND quantita_in_stock BETWEEN 1 AND 10";
} elseif ($stockFiltro === 'zero') {
    $sql .= " AND quantita_in_stock = 0";
}

$sql .= " ORDER BY nome_articolo ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articoli = $stmt->fetchAll(PDO::FETCH_ASSOC);

// categorie per il dropdown (prendo sempre tutte, non solo quelle filtrate)
$stmtCat = $pdo->query("SELECT DISTINCT categoria FROM articolo WHERE categoria IS NOT NULL ORDER BY categoria");
$categorie = $stmtCat->fetchAll(PDO::FETCH_COLUMN);

$haFiltri = $cerca !== '' || $catFiltro !== '' || $prezzoMin !== null || $prezzoMax !== null || $stockFiltro !== '';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Articoli</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <style>
        /* form filtri */
        .filtri-box {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
            background: var(--gray-900, #111827);
            border: 1px solid var(--gray-800, #1f2937);
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 15px;
        }

        .filtri-box .campo {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filtri-box label {
            font-size: 0.75rem;
            color: #9ca3af;
            font-weight: 600;
            text-transform: uppercase;
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

        .btn-cerca {
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 7px 16px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-cerca:hover { background: #2563eb; }

        .btn-reset {
            background: transparent;
            border: 1px solid #374151;
            color: #9ca3af;
            border-radius: 5px;
            padding: 7px 12px;
            font-size: 0.82rem;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-reset:hover { border-color: #6b7280; color: #e5e7eb; }

        .btn-inattivi {
            background: #7c3aed;
            color: #fff;
            border-radius: 6px;
            padding: 7px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-inattivi.attivo { background: #b91c1c; }

        /* badge filtri attivi */
        .badge-filtri {
            display: inline-block;
            background: #1d4ed8;
            color: #bfdbfe;
            font-size: 0.75rem;
            border-radius: 999px;
            padding: 2px 10px;
            margin-left: 8px;
        }

        /* barra alfabetica */
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
            user-select: none;
        }

        .lettera:hover, .lettera.selezionata {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
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

        .contatore b { color: #e5e7eb; }

        mark {
            background: #fbbf24;
            color: #111;
            border-radius: 2px;
            padding: 0 2px;
        }

        .stock-ok    { color: #34d399; }
        .stock-basso { color: #fbbf24; }
        .stock-zero  { color: #f87171; }

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
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:.75rem;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Dashboard
        </a>
        <a href="../articoli/index.php" class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:.75rem;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            Articoli
        </a>
        <a href="../fornitori/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:.75rem;"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            Fornitori
        </a>
        <a href="../ordini/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:.75rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Ordini
        </a>
        <a href="../dipendenti/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:.75rem;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Dipendenti
        </a>
        <a href="../scorte/index.php" style="border-left-color:#dc2626;color:#f87171;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:.75rem;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            Scorte Critiche
        </a>
        <a href="../../logout.php" style="border-top:1px solid var(--gray-800);margin-top:auto;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:.75rem;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Logout
        </a>
    </div>

    <div class="dashboard-content">

        <div class="page-header">
            <h1>
                Articoli
                <?php if ($inattivi): ?>
                    <small style="font-size:.55em;color:#a78bfa;">— non ordinati negli ultimi 6 mesi</small>
                <?php endif; ?>
                <?php if ($haFiltri): ?>
                    <span class="badge-filtri"><?= count($articoli) ?> risultati</span>
                <?php endif; ?>
            </h1>
            <div style="display:flex;gap:8px;align-items:center;">
                <a href="index.php?inattivi=<?= $inattivi ? 0 : 1 ?>"
                   class="btn-inattivi <?= $inattivi ? 'attivo' : '' ?>">
                    <?= $inattivi ? '✕ Tutti gli articoli' : '⏱ Non ordinati (6 mesi)' ?>
                </a>
                <a href="aggiungi.php" class="btn btn-primary">+ Aggiungi articolo</a>
            </div>
        </div>

        <!-- FORM FILTRI (lato server) -->
        <form method="GET" action="index.php">
            <?php if ($inattivi): ?>
                <input type="hidden" name="inattivi" value="1">
            <?php endif; ?>

            <div class="filtri-box">
                <div class="campo">
                    <label>Nome articolo</label>
                    <input type="text" name="cerca" value="<?= htmlspecialchars($cerca) ?>" placeholder="es. Penna...">
                </div>

                <div class="campo">
                    <label>Categoria</label>
                    <select name="categoria">
                        <option value="">Tutte</option>
                        <?php foreach ($categorie as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $catFiltro === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="campo">
                    <label>Prezzo da (€)</label>
                    <input type="number" name="prezzo_min" value="<?= htmlspecialchars($_GET['prezzo_min'] ?? '') ?>" placeholder="0.00" min="0" step="0.01" style="width:90px;">
                </div>

                <div class="campo">
                    <label>Prezzo a (€)</label>
                    <input type="number" name="prezzo_max" value="<?= htmlspecialchars($_GET['prezzo_max'] ?? '') ?>" placeholder="999.00" min="0" step="0.01" style="width:90px;">
                </div>

                <div class="campo">
                    <label>Stock</label>
                    <select name="stock">
                        <option value="">Tutti</option>
                        <option value="ok"    <?= $stockFiltro === 'ok'    ? 'selected' : '' ?>>Disponibile (&gt;10)</option>
                        <option value="basso" <?= $stockFiltro === 'basso' ? 'selected' : '' ?>>Basso (1–10)</option>
                        <option value="zero"  <?= $stockFiltro === 'zero'  ? 'selected' : '' ?>>Esaurito (0)</option>
                    </select>
                </div>

                <div style="display:flex;gap:6px;">
                    <button type="submit" class="btn-cerca">🔍 Cerca</button>
                    <a href="index.php<?= $inattivi ? '?inattivi=1' : '' ?>" class="btn-reset">✕ Reset filtri</a>
                </div>
            </div>
        </form>

        <!-- BARRA ALFABETICA (lato client, filtra sui risultati già ottenuti) -->
        <div class="alfabeto" id="alfabeto">
            <div class="lettera tutti selezionata" data-lettera="">Tutti</div>
            <?php foreach (range('A', 'Z') as $l): ?>
                <div class="lettera" data-lettera="<?= $l ?>"><?= $l ?></div>
            <?php endforeach; ?>
            <div class="lettera" data-lettera="#">#</div>
        </div>

        <div class="contatore">
            Articoli mostrati: <b id="contatore"><?= count($articoli) ?></b> / <b><?= count($articoli) ?></b>
        </div>

        <!-- TABELLA -->
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
            <tbody id="corpoTabella">

            <?php if (empty($articoli)): ?>
                <tr><td colspan="5" class="text-center">Nessun articolo trovato</td></tr>
            <?php endif; ?>

            <?php foreach ($articoli as $a):
                $nome   = $a['nome_articolo'] ?? '---';
                $cat    = $a['categoria'] ?? '---';
                $prezzo = $a['prezzo_unitario'] ?? 0;
                $stock  = (int)($a['quantita_in_stock'] ?? 0);

                if ($stock == 0) $cls = 'stock-zero';
                elseif ($stock <= 10) $cls = 'stock-basso';
                else $cls = 'stock-ok';

                $prima = strtoupper(substr($nome, 0, 1));
                if (!ctype_alpha($prima)) $prima = '#';

                // evidenzia il termine cercato nel nome
                $nomeHtml = htmlspecialchars($nome);
                if ($cerca !== '') {
                    $nomeHtml = preg_replace(
                        '/(' . preg_quote(htmlspecialchars($cerca), '/') . ')/i',
                        '<mark>$1</mark>',
                        $nomeHtml
                    );
                }
            ?>
                <tr data-lettera="<?= $prima ?>">
                    <td><?= $nomeHtml ?></td>
                    <td><?= htmlspecialchars($cat) ?></td>
                    <td>€ <?= number_format($prezzo, 2) ?></td>
                    <td class="<?= $cls ?>"><?= $stock == 0 ? 'Esaurito' : $stock ?></td>
                    <td class="actions">
                        <a href="modifica.php?id=<?= $a['id_articolo'] ?>" class="btn btn-warning">Modifica</a>
                        <a href="index.php?delete=<?= $a['id_articolo'] ?>"
                           class="btn btn-danger"
                           onclick="return confirm('Sei sicuro di voler eliminare questo articolo?');">
                           Elimina
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>

        <p id="nessun-risultato">Nessuna lettera corrisponde ai risultati trovati.</p>

    </div>
</div>

<script>
    // la barra alfabetica filtra solo le righe già restituite dal server
    var lettaraAttiva = '';
    var totaleServer  = <?= count($articoli) ?>;

    function filtraAlfabeto() {
        var righe    = document.querySelectorAll('#corpoTabella tr[data-lettera]');
        var visibili = 0;

        righe.forEach(function(riga) {
            var mostra = !lettaraAttiva || riga.dataset.lettera === lettaraAttiva;
            riga.style.display = mostra ? '' : 'none';
            if (mostra) visibili++;
        });

        document.getElementById('contatore').textContent = visibili;
        document.getElementById('nessun-risultato').style.display = visibili === 0 ? 'block' : 'none';
    }

    document.getElementById('alfabeto').addEventListener('click', function(e) {
        var btn = e.target.closest('.lettera');
        if (!btn) return;

        document.querySelectorAll('.lettera').forEach(function(b) { b.classList.remove('selezionata'); });
        btn.classList.add('selezionata');
        lettaraAttiva = btn.dataset.lettera;
        filtraAlfabeto();
    });
</script>

</body>
</html>