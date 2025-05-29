<?php
// Test script for handle-order.php

// Set up the request data
$data = [
    'orderId' => 2,
    'action' => 'update',
    'status' => 'Approved'
];

// Convert to JSON
$jsonData = json_encode($data);

// Initialize cURL
$ch = curl_init('http://localhost/CuptainsBrew/controllers/handle-order.php');

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

// Close cURL
curl_close($ch);

// Output results
echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Response:\n$response\n";

// Parse JSON response
$responseData = json_decode($response, true);
echo "\nParsed Response:\n";
print_r($responseData); 