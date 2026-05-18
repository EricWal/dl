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

// Path to cards folder
$cards_folder = __DIR__ . '/added_cards/';

// Get all PNG files from the cards folder
$files = glob($cards_folder . '*.png');

if (empty($files)) {
    die("No PNG files found in the cards folder.");
}

// Today's date for the date field in the database
$today = date('Y-m-d');

// Counter for inserted cards
$inserted = 0;
$deleted = 0; // Deleted from folder because they already exist in database
$kept = 0; // Kept in folder because they were inserted
$errors = [];

echo "Starting to insert cards into the database...\n";
echo "Total files found: " . count($files) . "\n\n";

// Prepare statements
$check_stmt = $conn->prepare("SELECT id FROM cards WHERE card_id = ?");
$insert_stmt = $conn->prepare("INSERT INTO cards (card_id, date, obtainable) VALUES (?, ?, ?)");

if ($check_stmt === false || $insert_stmt === false) {
    die("Prepare failed: " . $conn->error);
}

foreach ($files as $file) {
    // Get the filename without path
    $filename = basename($file);
    
    // Extract card ID - takes the number at the beginning before any space or #
    preg_match('/^(\d+)/', $filename, $matches);
    
    if (!isset($matches[1])) {
        $errors[] = "Could not extract card ID from: $filename";
        continue;
    }
    
    $card_id = (int)$matches[1];
    
    // Check if card already exists in database
    $check_stmt->bind_param("i", $card_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Card already exists - delete the image file
        if (unlink($file)) {
            echo "✓ Card $card_id already exists in database - image file DELETED\n";
            $deleted++;
        } else {
            $errors[] = "Card $card_id exists but failed to delete file: $filename";
        }
    } else {
        // Card doesn't exist - insert it into database
        $obtainable = false; // Set to false by default
        $insert_stmt->bind_param("isi", $card_id, $today, $obtainable);
        
        if ($insert_stmt->execute()) {
            echo "✓ Card $card_id inserted into database - image file KEPT\n";
            $inserted++;
            $kept++;
        } else {
            $errors[] = "Error inserting card $card_id: " . $insert_stmt->error;
        }
    }
}

// Display results
echo "================================================\n";
echo "INSERTION COMPLETE\n";
echo "================================================\n";
echo "Successfully inserted: $inserted cards (images kept in added_cards folder)\n";
echo "Already existed in database: $deleted cards (images DELETED from added_cards folder)\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach (array_slice($errors, 0, 10) as $error) { // Show first 10 errors
        echo "  - $error\n";
    }
    if (count($errors) > 10) {
        echo "  ... and " . (count($errors) - 10) . " more errors\n";
    }
}

// Get total cards in database
$result = $conn->query("SELECT COUNT(*) as total FROM cards");
$row = $result->fetch_assoc();
echo "\nTotal cards now in database: " . $row['total'] . "\n";

// Close prepared statements
$check_stmt->close();
$insert_stmt->close();

$conn->close();
echo "\nDone!\n";
?>
