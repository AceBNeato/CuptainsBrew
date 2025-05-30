<?php
// Mock POST data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['order_id'] = 2;
$_POST['status'] = 'Approved';

// Buffer output to capture the JSON response
ob_start();
require_once __DIR__ . '/controllers/handle-order.php';
$output = ob_get_clean();

// Display the output
echo "Response from handle-order.php:\n";
echo $output . "\n";

// Parse the JSON
$response = json_decode($output, true);
echo "\nParsed response:\n";
print_r($response); 