<?php
$envPath = __DIR__ . '/../.env';

$env = file_exists($envPath) ? parse_ini_file($envPath) : [];

$host = $env['DB_HOST'] ;
$dbname = $env['DB_NAME'] ;
$username = $env['DB_USERNAME'] ;
$password = $env['DB_PASSWORD'] ;

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, $username, $password, $options);


