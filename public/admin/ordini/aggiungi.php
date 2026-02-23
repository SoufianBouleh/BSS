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
                    <label for="stato_ordine">Stato ordine</label>
                    <select id="stato_ordine" name="stato_ordine">
                        <?php
                        $statoSel = $_POST['stato_ordine'] ?? 'inviato';
                        foreach (['inviato', 'confermato', 'consegnato', 'annullato', 'rifiutato'] as $stato):
                            ?>
                            <option value="<?= $stato ?>" <?= $statoSel === $stato ? 'selected' : '' ?>><?= ucfirst($stato) ?></option>
                        <?php endforeach; ?>
                    </select>
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
