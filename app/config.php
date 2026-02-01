<?php
$envPath = __DIR__ . '/../.env';

$env = parse_ini_file($envPath);

// Configurazione database
$host = $_env['DB_HOST'] ?? 'localhost';
$dbname = $_env['DB_NAME'] ?? 'gestione_fu';
$username = $_env['DB_USERNAME'] ?? 'gestione_fu';
$password = $_env['DB_PASSWORD'] ?? 'gestione.123';

$dsn = "mysql:host=$host;dbname=$dbname;password=$password";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];


$pdo = new PDO($dsn, $username, $password, $options);
