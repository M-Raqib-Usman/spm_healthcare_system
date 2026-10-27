<?php
require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("<h2 style='color:red;font-family:sans-serif;padding:40px;'>Database Connection Failed</h2>
         <p style='font-family:sans-serif;padding:0 40px;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>
         <p style='font-family:sans-serif;padding:0 40px;'>Please create the database using the provided schema.sql and sample_data.sql files in phpMyAdmin.</p>");
}
?>