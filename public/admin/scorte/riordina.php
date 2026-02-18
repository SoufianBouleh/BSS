<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/models/articolo.php';
require_once __DIR__ . '/../../../app/models/fornitore.php';
require_once __DIR__ . '/../../../app/models/ordine.php';

if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: ../../login.php');
    exit;
}

$articoloModel = new Articolo($pdo);
$fornitoreModel = new Fornitore($pdo);
$ordineModel    = new Ordine($pdo);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: index.php');
    exit;
}

$articolo  = $articoloModel->find($id);
if (!$articolo) {
    header('Location: index.php');
    exit;
}

$fornitori = $fornitoreModel->all();

// Fornitore preferito preselezionato
$fornitorePreferito = $articolo['id_fornitore_preferito'] ?? null;

// Quantità suggerita: almeno il doppio del punto di riordino, meno lo stock attuale
$qtaSuggerita = max(1, ($articolo['punto_riordino'] * 2) - $articolo['quantita_in_stock']);

$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dataOrdine   = $_POST['data_ordine'] ?? '';
    $dataConsegna = $_POST['data_consegna_prevista'] ?? '';
    $idFornitore  = (int)($_POST['id_fornitore'] ?? 0);
    $qta          = (int)($_POST['quantita'] ?? 0);
    $costoUnit    = (float)($articolo['prezzo_unitario']);

    if (!$dataOrdine)   $errors[] = 'Data ordine obbligatoria.';
    if (!$dataConsegna) $errors[] = 'Data consegna prevista obbligatoria.';
    if (!$idFornitore)  $errors[] = 'Seleziona un fornitore.';
    if ($qta <= 0)      $errors[] = 'Quantità deve essere maggiore di zero.';

   if (empty($errors)) {

    try {
        $pdo->beginTransaction();

        $costoTotale = round($costoUnit * $qta, 2);

        // 1️⃣ Crea ordine
        $stmt = $pdo->prepare("
            INSERT INTO ordine 
            (data_ordine, data_consegna_prevista, data_consegna_effettiva, stato_ordine, costo_totale, id_fornitore)
            VALUES (?, ?, NULL, 'inviato', ?, ?)
        ");

        $stmt->execute([
            $dataOrdine,
            $dataConsegna,
            $costoTotale,
            $idFornitore
        ]);

        $idOrdine = $pdo->lastInsertId();

        // 2️⃣ Salva dettaglio ordine
        $stmtDet = $pdo->prepare("
            INSERT INTO dettaglio_ordine
            (id_ordine, id_articolo, quantita, prezzo_unitario)
            VALUES (?, ?, ?, ?)
        ");

        $stmtDet->execute([
            $idOrdine,
            $articolo['id_articolo'],
            $qta,
            $costoUnit
        ]);

        $pdo->commit();
        $success = true;

    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "Errore nella creazione ordine.";
    }
}
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riordina — <?= htmlspecialchars($articolo['nome_articolo']) ?></title>
    <link rel="stylesheet" href="../../assets/css/style1.css">
    <style>
        .riordina-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d0000 100%);
            border-radius: 12px;
            padding: 1.75rem 2rem;
            margin-bottom: 2rem;
            border: 2px solid #dc2626;
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .riordina-header .icon { font-size: 2.5rem; }

        .riordina-header h1 {
            color: #fff;
            font-size: 1.5rem;
            margin: 0 0 0.2rem 0;
        }

        .riordina-header p {
            color: #f87171;
            margin: 0;
            font-size: 0.9rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            text-align: center;
        }

        .info-card .label {
            font-size: 0.75rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 0.4rem;
        }

        .info-card .value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--gray-900);
        }

        .info-card.critica .value { color: #dc2626; }
        .info-card.soglia .value  { color: #d97706; }
        .info-card.suggerita .value { color: #059669; }

        .form-crud label {
            display: block;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--gray-700);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group input,
        .form-group select {
            margin-bottom: 0;
        }

        .costo-preview {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            margin-top: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .costo-preview .label { color: var(--gray-600); font-size: 0.9rem; }
        .costo-preview .value { font-size: 1.5rem; font-weight: 800; color: var(--gray-900); }

        .btn-submit-riordina {
            width: 100%;
            padding: 1.25rem;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
        }

        .btn-submit-riordina:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220,38,38,0.35);
        }

        .alert-errors {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            color: #991b1b;
            margin-bottom: 1.5rem;
        }

        .alert-errors ul { margin: 0.5rem 0 0 1rem; }

        .success-box {
            background: #f0fdf4;
            border: 2px solid #16a34a;
            border-radius: 12px;
            padding: 2.5rem;
            text-align: center;
        }

        .success-box .icon { font-size: 3rem; margin-bottom: 1rem; }
        .success-box h2 { color: #15803d; margin-bottom: 0.5rem; }
        .success-box p { color: #166534; margin-bottom: 1.5rem; }
    </style>
</head>
<body>

<div class="dashboard-wrapper dashboard-admin">

    <div class="dashboard-sidebar">
        <div style="padding: 1.5rem; border-bottom: 1px solid var(--gray-800); display: flex; align-items: center; justify-content: center;">
            <img src="../assets/images/logo.png" alt="Logo" style="max-width: 120px; height: auto;">
        </div>
        <a href="../dashboard.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:.75rem">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>Dashboard
        </a>
        <a href="../articoli/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.75rem">
                <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>Articoli
        </a>
        <a href="../fornitori/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.75rem">
                <rect x="1" y="3" width="15" height="13"/>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>Fornitori
        </a>
        <a href="../ordini/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.75rem">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
            </svg>Ordini
        </a>
        <a href="../dipendenti/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.75rem">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
            </svg>Dipendenti
        </a>
        <a href="index.php" class="active" style="border-left-color: #dc2626; color: #f87171;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.75rem">
                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>⚠️ Scorte Critiche
        </a>
        <a href="../../logout.php" style="border-top: 1px solid var(--gray-800); margin-top: auto;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:.75rem">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>Logout
        </a>
    </div>

    <div class="dashboard-content">

        <!-- Header -->
        <div class="riordina-header">
            <div class="icon">📦</div>
            <div>
                <h1>Riordina — <?= htmlspecialchars($articolo['nome_articolo']) ?></h1>
                <p>
                    Categoria: <?= htmlspecialchars($articolo['categoria'] ?? '—') ?> &nbsp;|&nbsp;
                    Prezzo unitario: € <?= number_format($articolo['prezzo_unitario'], 2) ?>
                </p>
            </div>
            <a href="index.php" class="btn btn-warning" style="margin-left: auto;">← Torna alle scorte</a>
        </div>

        <!-- Info cards -->
        <div class="info-grid">
            <div class="info-card critica">
                <div class="label">Stock attuale</div>
                <div class="value"><?= $articolo['quantita_in_stock'] ?></div>
                <div style="font-size:0.8rem; color: var(--gray-500); margin-top: 0.3rem;"><?= htmlspecialchars($articolo['unita_misura']) ?></div>
            </div>
            <div class="info-card soglia">
                <div class="label">Punto riordino</div>
                <div class="value"><?= $articolo['punto_riordino'] ?></div>
                <div style="font-size:0.8rem; color: var(--gray-500); margin-top: 0.3rem;"><?= htmlspecialchars($articolo['unita_misura']) ?></div>
            </div>
            <div class="info-card">
                <div class="label">Deficit</div>
                <div class="value" style="color:#dc2626;">-<?= $articolo['punto_riordino'] - $articolo['quantita_in_stock'] ?></div>
                <div style="font-size:0.8rem; color: var(--gray-500); margin-top: 0.3rem;">unità mancanti</div>
            </div>
            <div class="info-card suggerita">
                <div class="label">Qta suggerita</div>
                <div class="value"><?= $qtaSuggerita ?></div>
                <div style="font-size:0.8rem; color: var(--gray-500); margin-top: 0.3rem;">per raddoppiare il min.</div>
            </div>
        </div>

        <?php if ($success): ?>
        <!-- Successo -->
        <div class="success-box">
            <div class="icon">✅</div>
            <h2>Ordine creato con successo!</h2>
            <p>L'ordine per <strong><?= htmlspecialchars($articolo['nome_articolo']) ?></strong> è stato registrato con stato <em>inviato</em>.</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="index.php" class="btn btn-primary">← Scorte critiche</a>
                <a href="../ordini/index.php" class="btn btn-warning">Vai agli ordini</a>
            </div>
        </div>

        <?php else: ?>
        <!-- Form -->
        <form method="POST" class="form-crud">

            <?php if (!empty($errors)): ?>
            <div class="alert-errors">
                <strong>⚠️ Correggi i seguenti errori:</strong>
                <ul>
                    <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Fornitore *</label>
                <select name="id_fornitore" required>
                    <option value="">— Seleziona fornitore —</option>
                    <?php foreach ($fornitori as $f): ?>
                    <option value="<?= $f['id_fornitore'] ?>"
                        <?= ($f['id_fornitore'] == $fornitorePreferito) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['nome_fornitore']) ?>
                        <?= ($f['id_fornitore'] == $fornitorePreferito) ? '⭐ (preferito)' : '' ?>
                        <?php if ($f['mail'] ?? ''): ?> — <?= htmlspecialchars($f['mail']) ?><?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Quantità da ordinare (<?= htmlspecialchars($articolo['unita_misura']) ?>) *</label>
                <input type="number"
                       name="quantita"
                       id="quantita"
                       min="1"
                       value="<?= $qtaSuggerita ?>"
                       required
                       oninput="aggiornaCosto(this.value)">
            </div>

            <!-- Anteprima costo -->
            <div class="costo-preview">
                <span class="label">💰 Costo stimato ordine</span>
                <span class="value" id="costoPreview">€ <?= number_format($articolo['prezzo_unitario'] * $qtaSuggerita, 2) ?></span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Data ordine *</label>
                    <input type="date" name="data_ordine" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Data consegna prevista *</label>
                    <input type="date" name="data_consegna_prevista" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" required>
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <button type="submit" class="btn-submit-riordina">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
                    Crea ordine di riordino
                </button>
            </div>

        </form>
        <?php endif; ?>

    </div>
</div>

<script>
const prezzoUnit = <?= (float)$articolo['prezzo_unitario'] ?>;
function aggiornaCosto(qta) {
    const tot = (parseFloat(qta) || 0) * prezzoUnit;
    document.getElementById('costoPreview').textContent = '€ ' + tot.toFixed(2).replace('.', ',');
}
</script>

</body>
</html>