<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$bgDir = __DIR__ . '/bg/';
$images = [];

if (is_dir($bgDir)) {
    $files = scandir($bgDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'png') {
            $images[] = $file;
        }
    }
    // Sort the images
    sort($images);
}

echo json_encode($images);
?>