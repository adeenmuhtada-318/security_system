<?php
// =====================================================
// DATABASE CONNECTION - db_connect.php
// Edit the values below to match your phpMyAdmin setup
// =====================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // your phpMyAdmin username
define('DB_PASS', '');            // your phpMyAdmin password (empty by default in XAMPP)
define('DB_NAME', 'security_firm');

function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,  // Prevents SQL injection
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Send error as JSON so frontend can show it
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
}
?>
