<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/richiesta.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'dipendente') {
    header('Location: ../../login.php');
    exit;
}

$richiesta = new Richiesta($pdo);

if (isset($_GET['delete'])) {
    $richiesta->delete((int)$_GET['delete']);
    header('Location: index.php');
    exit;
}

$richieste = $richiesta->all();

$stati = [];
foreach ($richieste as $r) {
    if (!empty($r['stato']) && !in_array($r['stato'], $stati, true)) {
        $stati[] = $r['stato'];
    }
}
sort($stati);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Le Mie Richieste</title>
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

        .stato-in-attesa {
            color: #fbbf24;
        }

        .stato-approvata {
            color: #34d399;
        }

        .stato-rifiutata {
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
        <a href="../articoli/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.75rem;">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
            Catalogo Articoli
        </a>
        <a href="../richieste/index.php" class="active">
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
            <h1>Le Mie Richieste</h1>
            <a href="aggiungi.php" class="btn btn-primary">+ Aggiungi Richiesta</a>
        </div>

        <div class="filtri-box">
            <div>
                <label>Cerca</label><br>
                <input type="text" id="cerca" placeholder="ID o note..." style="min-width:220px;">
            </div>
            <div>
                <label>Stato</label><br>
                <select id="filtroStato">
                    <option value="">Tutti</option>
                    <?php foreach ($stati as $stato): ?>
                        <option value="<?= htmlspecialchars($stato) ?>"><?= htmlspecialchars($stato) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Da data richiesta</label><br>
                <input type="date" id="filtroData">
            </div>
            <div style="align-self:flex-end;">
                <button class="btn-reset" id="btnReset">Reset</button>
            </div>
        </div>

        <div class="contatore">Richieste mostrate: <b id="contatore">0</b> / <b><?= count($richieste) ?></b></div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data richiesta</th>
                    <th>Data approvazione</th>
                    <th>Stato</th>
                    <th>Note</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody id="corpoTabella">
                <?php if (empty($richieste)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Nessuna richiesta presente</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($richieste as $r):
                    $id = (int)($r['id_richiesta'] ?? 0);
                    $dataRichiesta = $r['data_richiesta'] ?? '';
                    $dataApprovazione = $r['data_approvazione'] ?? '';
                    $stato = trim((string)($r['stato'] ?? ''));
                    $note = $r['note'] ?? '';

                    $statoNorm = strtolower($stato);
                    $statoCls = '';
                    if (strpos($statoNorm, 'approv') !== false) {
                        $statoCls = 'stato-approvata';
                    } elseif (strpos($statoNorm, 'rifiut') !== false || strpos($statoNorm, 'negat') !== false) {
                        $statoCls = 'stato-rifiutata';
                    } elseif (strpos($statoNorm, 'attes') !== false || strpos($statoNorm, 'pending') !== false) {
                        $statoCls = 'stato-in-attesa';
                    }
                    ?>
                    <tr
                        data-id="<?= $id ?>"
                        data-stato="<?= strtolower(htmlspecialchars($stato)) ?>"
                        data-note="<?= strtolower(htmlspecialchars($note)) ?>"
                        data-data="<?= htmlspecialchars($dataRichiesta) ?>"
                    >
                        <td><?= $id ?: '---' ?></td>
                        <td><?= htmlspecialchars($dataRichiesta ?: '---') ?></td>
                        <td><?= htmlspecialchars($dataApprovazione ?: '---') ?></td>
                        <td class="<?= $statoCls ?>"><?= htmlspecialchars($stato ?: '---') ?></td>
                        <td class="cella-note"><?= htmlspecialchars($note ?: '---') ?></td>
                        <td class="actions">
                            <a href="modifica.php?id=<?= $id ?>" class="btn btn-warning">Modifica</a>
                            <a href="index.php?delete=<?= $id ?>"
                               class="btn btn-danger"
                               onclick="return confirm('Eliminare questa richiesta?');">
                                Elimina
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p id="nessun-risultato">Nessuna richiesta corrisponde ai filtri scelti.</p>
    </div>
</div>

<script>
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
        var stato = document.getElementById('filtroStato').value.toLowerCase();
        var dataDa = document.getElementById('filtroData').value;
        var righe = document.querySelectorAll('#corpoTabella tr[data-id]');
        var visibili = 0;

        righe.forEach(function (riga) {
            var id = String(riga.dataset.id || '');
            var rStato = riga.dataset.stato || '';
            var note = riga.dataset.note || '';
            var data = riga.dataset.data || '';
            var mostra = true;

            if (cerca && id.indexOf(cerca) === -1 && note.indexOf(cerca) === -1) mostra = false;
            if (stato && rStato.indexOf(stato) === -1) mostra = false;
            if (dataDa && data && data < dataDa) mostra = false;

            riga.style.display = mostra ? '' : 'none';

            if (mostra) {
                evidenzia(riga.querySelector('.cella-note'), cerca);
                visibili++;
            }
        });

        document.getElementById('contatore').textContent = visibili;
        document.getElementById('nessun-risultato').style.display = visibili === 0 ? 'block' : 'none';
    }

    document.getElementById('cerca').addEventListener('input', aggiornaFiltri);
    document.getElementById('filtroStato').addEventListener('change', aggiornaFiltri);
    document.getElementById('filtroData').addEventListener('change', aggiornaFiltri);

    document.getElementById('btnReset').addEventListener('click', function () {
        document.getElementById('cerca').value = '';
        document.getElementById('filtroStato').value = '';
        document.getElementById('filtroData').value = '';
        aggiornaFiltri();
    });

    aggiornaFiltri();
</script>

</body>
</html>
