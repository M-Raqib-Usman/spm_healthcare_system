<?php
// =====================================================
// Smart Healthcare SPM System - Configuration
// =====================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // Change if your MySQL has a password
define('DB_NAME', 'spm_healthcare');

define('BASE_URL', '/spm_system/');   // Adjust according to your XAMPP/WAMP folder

// Thresholds for EVM warnings
define('CPI_THRESHOLD', 0.90);
define('SPI_THRESHOLD', 0.90);

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Karachi');
?>