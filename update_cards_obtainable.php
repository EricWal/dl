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

// Default values
$list_file = __DIR__ . '/update_cards_list.txt';
$obtainable_status = 1; // 1 for true, 0 for false

// Parse command line arguments
if ($argc > 1) {
    for ($i = 1; $i < $argc; $i++) {
        if ($argv[$i] === '--file' && isset($argv[$i + 1])) {
            $list_file = $argv[++$i];
        } elseif ($argv[$i] === '--status' && isset($argv[$i + 1])) {
            $obtainable_status = (int)$argv[++$i];
        } elseif ($argv[$i] === '--help') {
            echo "Usage: php update_cards_obtainable.php [OPTIONS]\n\n";
            echo "Options:\n";
            echo "  --file <path>     Path to cards list file (default: update_cards_list.txt)\n";
            echo "  --status <0|1>    Obtainable status: 0=false, 1=true (default: 1)\n";
            echo "  --help            Show this help message\n\n";
            echo "Examples:\n";
            echo "  php update_cards_obtainable.php\n";
            echo "  php update_cards_obtainable.php --status 0\n";
            echo "  php update_cards_obtainable.php --file update_cards_list.txt --status 1\n";
            exit(0);
        }
    }
}

// Check if file exists
if (!file_exists($list_file)) {
    die("Error: File '$list_file' not found.\n");
}

// Read the list file
$card_ids = file($list_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if (empty($card_ids)) {
    die("Error: No card IDs found in $list_file\n");
}

// Trim whitespace from each line
$card_ids = array_map('trim', $card_ids);
// Filter out empty lines and non-numeric values
$card_ids = array_filter($card_ids, function($id) {
    return !empty($id) && is_numeric($id);
});

if (empty($card_ids)) {
    die("Error: No valid card IDs found in $list_file\n");
}

echo "================================================\n";
echo "UPDATING CARDS OBTAINABLE STATUS\n";
echo "================================================\n";
echo "List file: $list_file\n";
echo "New obtainable status: " . ($obtainable_status ? "TRUE (1)" : "FALSE (0)") . "\n";
echo "Total cards to process: " . count($card_ids) . "\n\n";

// Update cards
$updated = 0;
$not_found = 0;
$errors = [];

// Prepare statement
$stmt = $conn->prepare("UPDATE cards SET obtainable = ? WHERE card_id = ?");

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

foreach ($card_ids as $card_id) {
    $card_id = (int)$card_id;
    $stmt->bind_param("ii", $obtainable_status, $card_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $updated++;
        } else {
            $not_found++;
        }
    } else {
        $errors[] = "Error updating card $card_id: " . $stmt->error;
    }
}

$stmt->close();

// Display results
echo "================================================\n";
echo "UPDATE COMPLETE\n";
echo "================================================\n";
echo "Successfully updated: $updated cards\n";
echo "Cards not found in database: $not_found\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach (array_slice($errors, 0, 10) as $error) {
        echo "  - $error\n";
    }
    if (count($errors) > 10) {
        echo "  ... and " . (count($errors) - 10) . " more errors\n";
    }
}

// Get updated status
$status_text = $obtainable_status ? "obtainable" : "not obtainable";
$result = $conn->query("SELECT COUNT(*) as total FROM cards WHERE obtainable = $obtainable_status");
$row = $result->fetch_assoc();
echo "\nTotal $status_text cards now in database: " . $row['total'] . "\n";

$conn->close();
echo "\nDone!\n";
?>
