<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cardhubmanager');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create tables with location_name if they don't exist
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL
)";

$sql_cards = "CREATE TABLE IF NOT EXISTS cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    location_name VARCHAR(100),
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql_users)) {
    die("Error creating users table: " . $conn->error);
}

if (!$conn->query($sql_cards)) {
    die("Error creating cards table: " . $conn->error);
}

// Add default admin if none exists
$result = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
if ($result->num_rows == 0) {
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    if (!$conn->query("INSERT INTO users (name, email, password, role) VALUES ('Admin', 'admin@cardhub.com', '$hashed_password', 'admin')")) {
        die("Error creating admin user: " . $conn->error);
    }
}
?>