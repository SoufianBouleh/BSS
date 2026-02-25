<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/ordine.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$ordine = new Ordine($pdo);
$fornitori = $ordine->getFornitori();
$articoli = $ordine->getArticoli();

$errori = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idsArticolo = $_POST['id_articolo'] ?? [];
    $quantita = $_POST['quantita'] ?? [];
    $righe = [];

    $max = max(count($idsArticolo), count($quantita));
    for ($i = 0; $i < $max; $i++) {
        $righe[] = [
            'id_articolo' => $idsArticolo[$i] ?? 0,
            'quantita' => $quantita[$i] ?? 0
        ];
    }

    try {
        $ordine->createWithItems($_POST, $righe);
        header('Location: index.php');
        exit;
    } catch (Throwable $e) {
        $errori[] = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Aggiungi Ordine</title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <style>
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .items-box {
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .item-row {
            display: grid;
            grid-template-columns: 1fr 120px auto;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .item-row:last-child {
            margin-bottom: 0;
        }

        .totale-preview {
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1rem;
            text-align: right;
        }

        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }

            .item-row {
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
        <a href="../dashboard.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Dashboard
        </a>
        <a href="../articoli/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            Articoli
        </a>
        <a href="../fornitori/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            Fornitori
        </a>
        <a href="../ordini/index.php" class="active">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Ordini
        </a>
        <a href="../richieste/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Richieste dipendenti
        </a>
        <a href="../dipendenti/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Dipendenti
        </a>
        <a href="../scorte/index.php" style="border-left-color: #dc2626; color: #f87171;" class="scorte-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            Scorte Critiche
        </a>
        <a href="../impostazioni/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h0a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h0a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v0a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            Impostazioni
        </a>
        <a href="../../logout.php" style="border-top: 1px solid var(--gray-800); margin-top: auto;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Logout
        </a>
    </div>

    <div class="dashboard-content">
        <div class="page-header">
            <h1>Nuovo Ordine</h1>
            <a href="index.php" class="btn btn-warning">Torna agli ordini</a>
        </div>

        <?php foreach ($errori as $errore): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errore) ?></div>
        <?php endforeach; ?>

        <form method="POST" class="form-crud">
            <div class="grid-2">
                <div>
                    <label for="data_ordine">Data ordine</label>
                    <input id="data_ordine" name="data_ordine" type="date" required value="<?= htmlspecialchars($_POST['data_ordine'] ?? date('Y-m-d')) ?>">
                </div>
                <div>
                    <label for="id_fornitore">Fornitore</label>
                    <select id="id_fornitore" name="id_fornitore" required>
                        <option value="">Seleziona fornitore</option>
                        <?php foreach ($fornitori as $f): ?>
                            <option value="<?= (int)$f['id_fornitore'] ?>" <?= ((int)($f['id_fornitore']) === (int)($_POST['id_fornitore'] ?? 0)) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['nome_fornitore']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="data_consegna_prevista">Data consegna prevista</label>
                    <input id="data_consegna_prevista" name="data_consegna_prevista" type="date" value="<?= htmlspecialchars($_POST['data_consegna_prevista'] ?? '') ?>">
                </div>
                <div>
                    <label>Stato ordine</label>
                    <input type="text" value="inviato (automatico)" readonly>
                </div>
            </div>

            <h3 style="margin: 1rem 0;">Articoli ordine</h3>
            <div class="items-box" id="itemsBox"></div>
            <button type="button" class="btn btn-info mb-2" id="btnAddRiga">+ Aggiungi articolo</button>
            <div class="totale-preview">Totale stimato: <span id="totaleStimato">&euro; 0.00</span></div>

            <button type="submit">Salva ordine</button>
        </form>
    </div>
</div>

<script>
    var articoli = <?= json_encode(array_values(array_map(function ($a) {
        return [
            'id_articolo' => (int)$a['id_articolo'],
            'nome_articolo' => $a['nome_articolo'],
            'prezzo_unitario' => (float)$a['prezzo_unitario']
        ];
    }, $articoli))) ?>;

    var box = document.getElementById('itemsBox');
    var btnAdd = document.getElementById('btnAddRiga');
    var totaleEl = document.getElementById('totaleStimato');
    var initialIds = <?= json_encode(array_values($_POST['id_articolo'] ?? [])) ?>;
    var initialQta = <?= json_encode(array_values($_POST['quantita'] ?? [])) ?>;

    function opzioniArticolo(selectedId) {
        var html = '<option value="">Seleziona articolo</option>';
        articoli.forEach(function(a) {
            var selected = String(a.id_articolo) === String(selectedId) ? ' selected' : '';
            html += '<option value="' + a.id_articolo + '"' + selected + ' data-price="' + a.prezzo_unitario + '">' +
                a.nome_articolo + ' (&euro; ' + a.prezzo_unitario.toFixed(2) + ')</option>';
        });
        return html;
    }

    function creaRiga(selectedId, qta) {
        var row = document.createElement('div');
        row.className = 'item-row';
        row.innerHTML =
            '<select name="id_articolo[]" required>' + opzioniArticolo(selectedId) + '</select>' +
            '<input name="quantita[]" type="number" min="1" step="1" value="' + (qta || 1) + '" required>' +
            '<button type="button" class="btn btn-danger">Rimuovi</button>';

        row.querySelector('button').addEventListener('click', function() {
            row.remove();
            calcolaTotale();
        });
        row.querySelector('select').addEventListener('change', calcolaTotale);
        row.querySelector('input').addEventListener('input', calcolaTotale);

        box.appendChild(row);
    }

    function calcolaTotale() {
        var totale = 0;
        box.querySelectorAll('.item-row').forEach(function(riga) {
            var select = riga.querySelector('select');
            var qta = parseInt(riga.querySelector('input').value || '0', 10);
            var opt = select.options[select.selectedIndex];
            var prezzo = opt ? parseFloat(opt.getAttribute('data-price') || '0') : 0;
            if (!isNaN(prezzo) && !isNaN(qta) && qta > 0) totale += prezzo * qta;
        });
        totaleEl.innerHTML = '&euro; ' + totale.toFixed(2);
    }

    btnAdd.addEventListener('click', function() {
        creaRiga('', 1);
        calcolaTotale();
    });

    if (initialIds.length > 0) {
        for (var i = 0; i < initialIds.length; i++) {
            creaRiga(initialIds[i], initialQta[i] || 1);
        }
    } else {
        creaRiga('', 1);
    }

    calcolaTotale();
</script>

</body>
</html>






