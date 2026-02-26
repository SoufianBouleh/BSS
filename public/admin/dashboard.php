<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['ruolo'] !== 'admin') {
    header('Location: ../dipendente/dashboard.php');
    exit;
}

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/models/articolo.php';
require_once __DIR__ . '/../../app/models/fornitore.php';
require_once __DIR__ . '/../../app/models/ordine.php';
require_once __DIR__ . '/../../app/models/dipendente.php';
require_once __DIR__ . '/../../app/models/richiesta.php';

$articoloModel = new Articolo($pdo);
$fornitoreModel = new Fornitore($pdo);
$ordineModel = new Ordine($pdo);
$dipendenteModel = new Dipendente($pdo);
$richiestaModel = new Richiesta($pdo);

$fileImpostazioni = __DIR__ . '/../../app/impostazioni.json';
$impostazioni = ['ordini_automatici_scorte' => false];
if (file_exists($fileImpostazioni)) {
    $tmp = json_decode((string)file_get_contents($fileImpostazioni), true);
    if (is_array($tmp)) {
        $impostazioni = array_merge($impostazioni, $tmp);
    }
}
if (!empty($impostazioni['ordini_automatici_scorte'])) {
    $ordineModel->creaAutomaticiDaScorteCritiche();
}

$articoli = $articoloModel->tutti();
$fornitori = $fornitoreModel->tutti();
$ordini = $ordineModel->tutti();
$dipendenti = $dipendenteModel->tutti();
$richieste = $richiestaModel->tuttePerAdmin();

// Calcolo scorte critiche (quantità < punto riordino)
$scorteCritiche = array_filter($articoli, function($art) {
    return isset($art['quantita_in_stock']) && 
           isset($art['punto_riordino']) && 
           $art['quantita_in_stock'] < $art['punto_riordino'];
});

$countArticoli = count($articoli);
$countFornitori = count($fornitori);
$countOrdini = count($ordini);
$countDipendenti = count($dipendenti);
$countScorteCritiche = count($scorteCritiche);
$countRichieste = count($richieste);

// Costo totale forniture ultimo anno, raggruppato per fornitore
$sqlCostoFornitori = "SELECT f.id_fornitore,
                             f.nome_fornitore,
                             SUM(c.prezzo) AS totale_costo
                      FROM ordine o
                      INNER JOIN fornitore f ON f.id_fornitore = o.id_fornitore
                      INNER JOIN comprende c ON c.id_ordine = o.id_ordine
                      WHERE o.data_ordine >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                        AND o.stato_ordine IN ('confermato', 'consegnato')
                      GROUP BY f.id_fornitore, f.nome_fornitore
                      ORDER BY totale_costo DESC";
$stmtCostoFornitori = $pdo->prepare($sqlCostoFornitori);
$stmtCostoFornitori->execute();
$costiPerFornitore = $stmtCostoFornitori->fetchAll(PDO::FETCH_ASSOC);

$costoTotaleUltimoAnno = 0.0;
$maxCostoFornitore = 0.0;
foreach ($costiPerFornitore as $rigaCosto) {
    $val = (float)($rigaCosto['totale_costo'] ?? 0);
    $costoTotaleUltimoAnno += $val;
    if ($val > $maxCostoFornitore) {
        $maxCostoFornitore = $val;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="../assets/css/style1.css">
    <link rel="stylesheet" href="../assets/css/pages/admin-dashboard.css">
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">

    <?php
$sidebarBase = '';
$assetPrefix = '../assets';
$logoutPath = '../logout.php';
$activeSection = 'dashboard';
include __DIR__ . '/includes/sidebar.php';
?>

    <div class="dashboard-content"> 
        <div class="stats-grid">
            <a href="./articoli/index.php" class="stat-link" aria-label="Vai alla sezione Articoli">
            <div class="stat-card" style="border-top: 3px solid #3b82f6;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3>Articoli Totali</h3>
                        <div class="stat-value" style="color: #3b82f6;"><?= $countArticoli ?></div>
                    </div>
                    <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); padding: 1rem; border-radius: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                    </div>
                </div>  
            </div>
            </a>
            
            
            <a href="fornitori/index.php" class="stat-link" aria-label="Vai alla sezione Fornitori">
            <div class="stat-card" style="border-top: 3px solid #8b5cf6;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3>Fornitori Attivi</h3>
                        <div class="stat-value" style="color: #8b5cf6;"><?= $countFornitori ?></div>
                    </div>
                    <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 1rem; border-radius: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="3" width="15" height="13"></rect>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                            <circle cx="5.5" cy="18.5" r="2.5"></circle>
                            <circle cx="18.5" cy="18.5" r="2.5"></circle>
                        </svg>
                    </div>
                </div>
            </div>
            </a>
            
            <a href="ordini/index.php" class="stat-link" aria-label="Vai alla sezione Ordini">
            <div class="stat-card" style="border-top: 3px solid #10b981;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3>Ordini</h3>
                        <div class="stat-value" style="color: #10b981;"><?= $countOrdini ?></div>
                    </div>
                    <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 1rem; border-radius: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                </div>
            </div>
            </a>
            
            <a href="dipendenti/index.php" class="stat-link" aria-label="Vai alla sezione Dipendenti">
            <div class="stat-card" style="border-top: 3px solid #f59e0b;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3>Dipendenti</h3>
                        <div class="stat-value" style="color: #f59e0b;"><?= $countDipendenti ?></div>
                    </div>
                    <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 1rem; border-radius: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                </div>
            </div>
            </a>

            <a href="richieste/index.php" class="stat-link" aria-label="Vai alla sezione Richieste dipendenti">
            <div class="stat-card" style="border-top: 3px solid #0ea5e9;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h3 style="text-transform:none;">Richieste dipendenti</h3>
                        <div class="stat-value" style="color: #0ea5e9;"><?= $countRichieste ?></div>
                    </div>
                    <div style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); padding: 1rem; border-radius: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            </a>
        </div>
        <hr>
        <div class="analytics-card">
            <h2 class="analytics-title">Costo forniture ultimo anno per fornitore</h2>
            <p class="analytics-sub">Somma costi per ordini confermati/consegnati, raggruppata per fornitore.</p>
            <div class="analytics-total">Totale aggregato: EUR <?= number_format($costoTotaleUltimoAnno, 2, ',', '.') ?></div>

            <div class="chart-wrap">
                <canvas id="fornitoriBarChart"></canvas>
                <div id="fornitori-chart-empty" class="chart-empty" style="display:none;">Nessun dato disponibile nell'ultimo anno.</div>
            </div>
        </div>
       

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const data = <?= json_encode(array_map(function ($riga) {
        return [
            'nome' => (string)($riga['nome_fornitore'] ?? ''),
            'totale' => (float)($riga['totale_costo'] ?? 0)
        ];
    }, $costiPerFornitore), JSON_UNESCAPED_UNICODE) ?>;

    const canvas = document.getElementById('fornitoriBarChart');
    const empty = document.getElementById('fornitori-chart-empty');
    if (!canvas || !empty) return;

    if (!Array.isArray(data) || data.length === 0) {
        canvas.style.display = 'none';
        empty.style.display = 'block';
        return;
    }

    if (typeof Chart === 'undefined') {
        canvas.style.display = 'none';
        empty.style.display = 'block';
        empty.textContent = 'Chart.js non caricato.';
        return;
    }

    const labels = data.map(item => item.nome || 'N/D');
    const values = data.map(item => Number(item.totale || 0));

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Costo totale (EUR)',
                data: values,
                borderRadius: 8,
                backgroundColor: 'rgba(180, 83, 9, 0.85)',
                borderColor: 'rgba(146, 64, 14, 1)',
                borderWidth: 1.2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            const val = Number(ctx.parsed.y || 0);
                            return ' EUR ' + val.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: '#374151', maxRotation: 35, minRotation: 0 },
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#4b5563',
                        callback: function(value) {
                            return 'EUR ' + Number(value).toLocaleString('it-IT');
                        }
                    },
                    grid: { color: 'rgba(107, 114, 128, 0.15)' }
                }
            }
        }
    });
})();
</script>


</body>
</html>














