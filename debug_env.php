<?php
echo "<pre>";
echo "MYSQLHOST: " . var_export(getenv('MYSQLHOST'), true) . "\n";
echo "MYSQLPORT: " . var_export(getenv('MYSQLPORT'), true) . "\n";
echo "MYSQLUSER: " . var_export(getenv('MYSQLUSER'), true) . "\n";
echo "MYSQLPASSWORD: " . var_export(getenv('MYSQLPASSWORD'), true) . "\n";
echo "MYSQLDATABASE: " . var_export(getenv('MYSQLDATABASE'), true) . "\n";
echo "MYSQL_URL: " . var_export(getenv('MYSQL_URL'), true) . "\n";
echo "MYSQL_ADDON_HOST: " . var_export(getenv('MYSQL_ADDON_HOST'), true) . "\n";
echo "MYSQL_ADDON_URI: " . var_export(getenv('MYSQL_ADDON_URI'), true) . "\n";
echo "RAILWAY_PUBLIC_DOMAIN: " . var_export(getenv('RAILWAY_PUBLIC_DOMAIN'), true) . "\n";

echo "\n--- All env vars ---\n";
print_r($_SERVER);
echo "</pre>";
