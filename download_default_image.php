<?php
// URL of a default food image from a reliable source
$imageUrl = 'https://raw.githubusercontent.com/microsoft/fluentui-emoji/main/assets/Fork%20and%20knife%20with%20plate/3D/fork_and_knife_with_plate_3d.png';
$savePath = __DIR__ . '/assets/images/default-food.jpg';

// Create directory if it doesn't exist
if (!file_exists(dirname($savePath))) {
    mkdir(dirname($savePath), 0777, true);
}

// Download and save the image
$imageContent = file_get_contents($imageUrl);
if ($imageContent !== false) {
    file_put_contents($savePath, $imageContent);
    echo "Default food image has been downloaded successfully to assets/images/default-food.jpg!\n";
} else {
    echo "Error downloading the image.\n";
}