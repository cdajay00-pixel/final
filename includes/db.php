<?php
$url = getenv('MYSQL_URL') ?: getenv('MYSQL_ADDON_URI');
if ($url) {
    $parts = parse_url($url);
    $host = $parts['host'] ?? '127.0.0.1';
    $user = $parts['user'] ?? 'root';
    $pass = $parts['pass'] ?? '';
    $dbname = ltrim($parts['path'] ?? '', '/') ?: 'railway';
    $port = $parts['port'] ?? '3306';
} else {
    $host = getenv('MYSQLHOST') ?: getenv('MYSQL_ADDON_HOST') ?: 'acela.proxy.rlwy.net';
    $user = getenv('MYSQLUSER') ?: getenv('MYSQL_ADDON_USER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_ADDON_PASSWORD') ?: 'SagVkYSQpOvDpHzalFYqDvWksBAvptiR';
    $dbname = getenv('MYSQLDATABASE') ?: getenv('MYSQL_ADDON_DATABASE') ?: 'railway';
    $port = getenv('MYSQLPORT') ?: getenv('MYSQL_ADDON_PORT') ?: '59470';
}

$conn = mysqli_init();
$conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
$conn->real_connect($host, $user, $pass, $dbname, (int)$port, NULL, MYSQLI_CLIENT_SSL);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>