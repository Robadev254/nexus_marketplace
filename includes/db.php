<?php
// includes/db.php
// Load .env variables
$env_path = dirname(__DIR__) . '/.env';
if (file_exists($env_path)) {
    $env = parse_ini_file($env_path);
    $host = $env['DB_HOST'];
    $user = $env['DB_USER'];
    $pass = $env['DB_PASS'];
    $db   = $env['DB_NAME'];
} else {
    // Fallback if .env is missing
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "marketplace";
}

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>