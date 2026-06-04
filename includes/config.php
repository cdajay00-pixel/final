<?php
// Site Configuration
define('SITE_NAME', 'ADSSU LAMS');
define('SITE_URL', 'http://localhost/adssu_lams_borrower/');
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