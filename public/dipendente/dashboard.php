<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}


if ($_SESSION['ruolo'] !== 'dipendente') {
    // se non sei dipendente, vai all’admin dashboard
    header('Location: ../admin/dashboard.php');
    exit;
}

// qui va il contenuto della dashboard dipendente
echo "<h1>Benvenuto dipendente {$_SESSION['username']}</h1>";
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
       <link rel="stylesheet" href="../assets/css/style.css">
    <img src="../assets/images/logo.png" class="logo" alt="Logo">
    </head>
    <body>

        <div class="dashboard-wrapper dashboard-dipendente">
    <div class="dashboard-sidebar">
        <h2>Dipendente</h2>
        <a href="dashboard.php">Home</a>
        <a href="richieste/index.php">Le mie richieste</a>
        <a href="profilo.php">Profilo</a>
    </div>
    <div class="dashboard-content">
        <h1>Benvenuto dipendente</h1>
        <p>Contenuto della dashboard...</p>
    </div>
</div>
    </body>
</html>
