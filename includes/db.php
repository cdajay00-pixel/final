<?php
$url = getenv('MYSQL_URL') ?: getenv('MYSQL_ADDON_URI');
if ($url) {
    $parts = parse_url($url);
    $host = $parts['host'] ?? '127.0.0.1';
    $user = $parts['user'] ?? 'root';
    $pass = $parts['pass'] ?? '';
    $dbname = ltrim($parts['path'] ?? '', '/') ?: 'adssu_lams';
    $port = $parts['port'] ?? '3306';
} else {
    $host = getenv('MYSQLHOST') ?: getenv('MYSQL_ADDON_HOST') ?: '127.0.0.1';
    $user = getenv('MYSQLUSER') ?: getenv('MYSQL_ADDON_USER') ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_ADDON_PASSWORD') ?: '';
    $dbname = getenv('MYSQLDATABASE') ?: getenv('MYSQL_ADDON_DATABASE') ?: 'adssu_lams';
    $port = getenv('MYSQLPORT') ?: getenv('MYSQL_ADDON_PORT') ?: '3306';
}

$conn = new mysqli($host, $user, $pass, $dbname, (int)$port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>