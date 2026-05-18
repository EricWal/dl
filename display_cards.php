<?php
// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $env_lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'\'');
            $_ENV[$key] = $value;
        }
    }
}

// Database configuration from .env
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_user = $_ENV['DB_USER'] ?? 'root';
$db_password = $_ENV['DB_PASSWORD'] ?? '';
$db_name = $_ENV['DB_NAME'] ?? 'cards_db';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Query cards with obtainable = 1
$sql = "SELECT card_id FROM cards WHERE obtainable = 0";
$result = $conn->query($sql);

$output = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = (int)$row['card_id'];
        $hex = strtoupper(bin2hex(pack("V", $id)));
        $hexFormatted = implode(' ', str_split($hex, 2));
        $output[] = $hexFormatted;
    }
}

echo implode(' ', $output);

$conn->close();
?>
