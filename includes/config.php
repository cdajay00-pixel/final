<?php
// Site Configuration
define('SITE_NAME', 'ADSSU LAMS');
$base_url = getenv('RAILWAY_PUBLIC_DOMAIN')
    ? 'https://' . getenv('RAILWAY_PUBLIC_DOMAIN') . '/'
    : 'http://localhost/adssu_lams_borrower/';
define('SITE_URL', $base_url);
define('BORROWER_URL', SITE_URL . 'borrower/');

// Borrowing Rules
define('MAX_BORROW_DAYS', 14);
define('MAX_BORROW_ITEMS', 5);

// Timezone
date_default_timezone_set('Asia/Manila');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>