<?php
// Start a session to simulate a logged-in user
session_start();
$_SESSION['user_id'] = 1; // Assuming user ID 1 exists

// Create a sample order
$orderData = [
    'items' => [
        [
            'menu_item_id' => 1, // Assuming menu item ID 1 exists
            'quantity' => 1,
            'unit_price' => 100
        ]
    ],
    'subtotal' => 100,
    'discount_type' => '0',
    'discount_amount' => 0,
    'total' => 100,
    'amount_received' => 100,
    'change' => 0,
    'payment_method' => 'cash',
    'notes' => 'Test order'
];

// Convert to JSON
$jsonData = json_encode($orderData);

// Create a temporary file with the JSON data
$tempFile = tempnam(sys_get_temp_dir(), 'order_');
file_put_contents($tempFile, $jsonData);

// Use cURL to send the request
$ch = curl_init('http://localhost/ERC-POS/handlers/orders/create.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Clean up
unlink($tempFile);

// Output the result
echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response:\n$response\n"; 