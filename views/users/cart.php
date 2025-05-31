<?php
session_start();
require_once __DIR__ . '/../../config.php';

// Define cafe location constants for Tagum City
if (!defined('CAFE_LOCATION_LAT')) {
define('CAFE_LOCATION_LAT', 7.4478); // Tagum City coordinates
}
if (!defined('CAFE_LOCATION_LON')) {
    define('CAFE_LOCATION_LON', 125.8078); // Tagum City coordinates
}

// Ensure user is logged in
function ensure_user_logged_in() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /views/auth/login.php");
        exit;
    }
    return $_SESSION['user_id'];
}

// Fetch cart items
function get_cart_items($conn, $user_id) {
    $sql = "SELECT c.id, c.product_id, c.quantity, c.variation, p.item_name, p.item_price, p.item_image,
            pv.price as variation_price
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            LEFT JOIN product_variations pv ON c.product_id = pv.product_id AND c.variation = pv.variation_type
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart_items = [];
    $total = 0;

    while ($row = $result->fetch_assoc()) {
        // Use variation price if available, otherwise use base price
        $price = $row['variation_price'] ? $row['variation_price'] : $row['item_price'];
        $subtotal = $row['quantity'] * $price;
        $total += $subtotal;
        $cart_items[] = [
            'cart_id' => $row['id'],
            'product_id' => $row['product_id'],
            'name' => htmlspecialchars($row['item_name']),
            'price' => $price,
            'base_price' => $row['item_price'],
            'quantity' => $row['quantity'],
            'image' => htmlspecialchars($row['item_image']),
            'variation' => $row['variation'],
            'subtotal' => $subtotal
        ];
    }
    $stmt->close();
    return ['items' => $cart_items, 'total' => $total];
}

// Fetch user address
function get_user_address($conn, $user_id) {
    $sql = "SELECT address, contact FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $data = [
        'address' => $row['address'] ?? '',
        'contact' => $row['contact'] ?? ''
    ];
    $stmt->close();
    return $data;
}

// Simple checkout function
function simple_checkout($conn, $user_id, $subtotal, $delivery_address, $payment_method, $delivery_fee, $lat, $lon, $contact_number, $selected_cart_ids = []) {
    try {
        // Start transaction
        $conn->begin_transaction();
    
        // 1. Create the order record
    $total = $subtotal + $delivery_fee;
        $status = 'Pending';
        
        $order_sql = "INSERT INTO orders (user_id, total_amount, status, delivery_address, payment_method, delivery_fee, lat, lon, customer_contact) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $order_stmt = $conn->prepare($order_sql);
        
        // Convert lat/lon to strings to avoid type issues
        $lat_str = strval($lat);
        $lon_str = strval($lon);
        
        $order_stmt->bind_param("idsssdsss", $user_id, $total, $status, $delivery_address, $payment_method, $delivery_fee, $lat_str, $lon_str, $contact_number);
        
        if (!$order_stmt->execute()) {
            throw new Exception("Failed to create order: " . $order_stmt->error);
        }
        
        $order_id = $conn->insert_id;
        $order_stmt->close();
        
        // 2. Get cart items
        if (empty($selected_cart_ids)) {
            // If no specific items selected, get all cart items
            $cart_data = get_cart_items($conn, $user_id);
            $cart_items = $cart_data['items'];
        } else {
            // Get only selected cart items
            $ids_string = implode(',', array_map('intval', $selected_cart_ids));
        
            $sql = "SELECT c.id, c.product_id, c.quantity, c.variation, p.item_name, p.item_price, p.item_image,
                    pv.price as variation_price
                    FROM cart c 
                    JOIN products p ON c.product_id = p.id 
                    LEFT JOIN product_variations pv ON c.product_id = pv.product_id AND c.variation = pv.variation_type
                    WHERE c.user_id = ? AND c.id IN ($ids_string)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $cart_items = [];
            while ($row = $result->fetch_assoc()) {
                // Use variation price if available, otherwise use base price
                $price = $row['variation_price'] ? $row['variation_price'] : $row['item_price'];
                $cart_items[] = [
                    'cart_id' => $row['id'],
                    'product_id' => $row['product_id'],
                    'name' => $row['item_name'],
                    'price' => $price,
                    'quantity' => $row['quantity'],
                    'variation' => $row['variation']
                ];
            }
            $stmt->close();
        }
        
        // 3. Insert order items
        $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price, variation) VALUES (?, ?, ?, ?, ?)";
        $item_stmt = $conn->prepare($item_sql);
        
        foreach ($cart_items as $item) {
            $item_stmt->bind_param("iiids", $order_id, $item['product_id'], $item['quantity'], $item['price'], $item['variation']);
            
            if (!$item_stmt->execute()) {
                throw new Exception("Failed to add order item: " . $item_stmt->error);
            }
        }
        $item_stmt->close();
        
        // 4. Create payment record
        $payment_sql = "INSERT INTO payments (order_id, amount, method, status) VALUES (?, ?, ?, 'Pending')";
        $payment_stmt = $conn->prepare($payment_sql);
        $payment_stmt->bind_param("ids", $order_id, $total, $payment_method);
        
        if (!$payment_stmt->execute()) {
            throw new Exception("Failed to create payment: " . $payment_stmt->error);
        }
        $payment_stmt->close();
        
        // 5. Remove processed items from cart
        if (empty($selected_cart_ids)) {
            // Clear all items from user's cart
            $clear_sql = "DELETE FROM cart WHERE user_id = ?";
            $clear_stmt = $conn->prepare($clear_sql);
            $clear_stmt->bind_param("i", $user_id);
        } else {
            // Clear only selected items
            $ids_string = implode(',', array_map('intval', $selected_cart_ids));
            $clear_sql = "DELETE FROM cart WHERE user_id = ? AND id IN ($ids_string)";
            $clear_stmt = $conn->prepare($clear_sql);
            $clear_stmt->bind_param("i", $user_id);
        }
        
        if (!$clear_stmt->execute()) {
            throw new Exception("Failed to clear cart: " . $clear_stmt->error);
        }
        $clear_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'order_id' => $order_id
        ];
    } 
    catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Main execution
$user_id = ensure_user_logged_in();
$cart_data = get_cart_items($conn, $user_id);
$cart_items = $cart_data['items'];
$subtotal = $cart_data['total'];
$user_data = get_user_address($conn, $user_id);
$user_address = $user_data['address'];
$user_contact = $user_data['contact'];

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $delivery_address = $_POST['delivery_address'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'Cash on Delivery (COD)';
    $delivery_fee = isset($_POST['delivery_fee']) ? floatval($_POST['delivery_fee']) : 30.00;
    $lat = $_POST['lat'] ?? CAFE_LOCATION_LAT;
    $lon = $_POST['lon'] ?? CAFE_LOCATION_LON;
    $selected_items_json = $_POST['selected_items'] ?? '';
    $contact_number = $_POST['contact_number'] ?? $user_contact;
    
    // If payment method contains COD, standardize it
    if (stripos($payment_method, 'COD') !== false) {
        $payment_method = 'COD';
    }
    
    // Simple validation
    if (empty($cart_items)) {
        $error = "Your cart is empty. Please add items before checkout.";
    } else if (empty($selected_items_json)) {
        $error = "No items selected for checkout.";
    } else {
        try {
            // Decode the selected items
            $selected_cart_ids = json_decode($selected_items_json, true);
            
            if (!is_array($selected_cart_ids) || empty($selected_cart_ids)) {
                throw new Exception("Invalid item selection.");
            }
            
            // Filter cart items to only include selected ones
            $selected_cart_items = array_filter($cart_items, function($item) use ($selected_cart_ids) {
                return in_array($item['cart_id'], $selected_cart_ids);
            });
            
            // Calculate subtotal for selected items only
            $selected_subtotal = 0;
            foreach ($selected_cart_items as $item) {
                $selected_subtotal += $item['subtotal'];
            }
            
            // Use the selected subtotal for checkout
            $result = simple_checkout($conn, $user_id, $selected_subtotal, $delivery_address, $payment_method, $delivery_fee, $lat, $lon, $contact_number, $selected_cart_ids);
            
        if ($result['success']) {
            header("Location: /views/users/User-Purchase.php?order_id=" . $result['order_id']);
            exit;
        } else {
            $error = $result['error'];
                echo "<script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Checkout Error',
                        text: '" . addslashes($error) . "',
                        confirmButtonColor: '#2C6E8A'
                    });
                </script>";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Checkout Error',
                    text: '" . addslashes($error) . "',
                    confirmButtonColor: '#2C6E8A'
                });
            </script>";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cart - Captain's Brew Cafe</title>
    <link rel="icon" href="/public/images/LOGO.png" sizes="any">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .modal {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .cart-summary {
            position: sticky;
            top: 20px;
        }
        .address-container {
            position: relative;
        }
        #address_suggestions {
            position: absolute;
            z-index: 1000;
            background: white;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 0.375rem;
            max-height: 200px;
            overflow-y: auto;
            display: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        #map_preview {
            height: 200px;
            border-radius: 0.375rem;
            margin-top: 1rem;
            border: 1px solid #ddd;
        }
        .location-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background-color: #2C6E8A;
            color: white;
            border: none;
            border-radius: 0.375rem;
            padding: 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
            z-index: 10;
        }
        .location-btn:hover {
            background-color: #235A73;
        }
        .swal-wide {
            width: 600px !important;
        }
        
        /* Responsive styles for mobile devices */
        @media (max-width: 768px) {
            .location-btn {
                font-size: 0.7rem;
                padding: 0.4rem;
                right: 5px;
            }
            
            #map_preview {
                height: 150px;
            }
            
            .py-4.flex.items-center {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .flex-shrink-0.w-20.h-20 {
                margin-bottom: 1rem;
                width: 100%;
                height: 120px;
            }
            
            .ml-4.flex-1 {
                margin-left: 0;
                width: 100%;
            }
            
            .flex.items-center.justify-between {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .flex.items-center.justify-between > div:last-child {
                margin-top: 0.5rem;
                align-self: flex-end;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <header class="bg-gradient-to-r from-[#FFFAEE] to-[#FFDBB5] shadow sticky top-0 z-10">
        <div class="container mx-auto px-4 py-4 flex items-center justify-center">
            <img src="/public/images/LOGO.png" alt="Captain's Brew Cafe Logo" class="h-12 transform hover:scale-105 transition">
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <div class="flex items-center mb-6">
            <a href="/views/users/User-Menu.php" class="text-[#2C6E8A] hover:text-[#235A73] flex items-center text-lg font-medium">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Menu
            </a>
        </div>

        <h1 class="text-3xl font-bold text-[#2C6E8A] text-center mb-8">Your Cart</h1>

        <?php if (empty($cart_items)): ?>
            <div class="bg-[#A9D6E5] rounded-lg p-8 text-center">
                <p class="text-lg text-[#4a3b2b]">Your cart is empty.</p>
                <a href="/views/users/User-Menu.php" class="mt-4 inline-block bg-[#2C6E8A] text-white py-2 px-6 rounded-lg hover:bg-[#235A73] transition">Browse Menu</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-[#2C6E8A] mb-4">Cart Items</h2>
                            
                            <!-- Select All Checkbox -->
                            <div class="flex items-center mb-4 pb-2 border-b border-gray-200">
                                <input type="checkbox" id="select-all" class="w-5 h-5 text-[#2C6E8A] rounded border-gray-300 focus:ring-[#2C6E8A]">
                                <label for="select-all" class="ml-2 text-gray-700 font-medium">Select All Items</label>
                                <div class="ml-auto">
                                    <button type="button" id="remove-selected" class="text-red-500 hover:text-red-700 px-3 py-1 rounded-md border border-red-500 hover:border-red-700 text-sm mr-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <i class="fas fa-trash mr-1"></i> Remove Selected
                                    </button>
                                </div>
                            </div>
                            
                            <div class="divide-y divide-gray-200">
                    <?php foreach ($cart_items as $item): ?>
                                    <div class="py-4 flex items-center">
                                        <!-- Item Checkbox -->
                                        <div class="mr-4">
                                            <input type="checkbox" id="cart-item-<?= $item['cart_id'] ?>" 
                                                   class="cart-item-checkbox w-5 h-5 text-[#2C6E8A] rounded border-gray-300 focus:ring-[#2C6E8A]"
                                                   data-cart-id="<?= $item['cart_id'] ?>"
                                                   data-price="<?= $item['price'] ?>"
                                                   data-quantity="<?= $item['quantity'] ?>">
                                        </div>
                                        
                                        <div class="flex-shrink-0 w-20 h-20 bg-gray-100 rounded-md overflow-hidden">
                                            <?php if ($item['image']): ?>
                                                <img src="/public/<?= $item['image'] ?>" alt="<?= $item['name'] ?>" class="w-full h-full object-cover" onerror="this.src='/public/images/placeholder.jpg';">
                                            <?php else: ?>
                                                <img src="/public/images/placeholder.jpg" alt="Placeholder" class="w-full h-full object-cover">
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <h3 class="text-lg font-medium text-[#4a3b2b]"><?= $item['name'] ?></h3>
                                            <?php if ($item['variation']): ?>
                                                <p class="text-sm text-gray-500">Variation: <?= htmlspecialchars($item['variation']) ?></p>
                                            <?php endif; ?>
                                            <div class="flex items-center justify-between mt-2">
                                                <div class="flex items-center">
                                                    <button type="button" onclick="updateQuantity(<?= $item['cart_id'] ?>, <?= $item['quantity'] - 1 ?>)" class="text-gray-500 hover:text-[#2C6E8A] focus:outline-none">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                        </svg>
                                                    </button>
                                                    <span class="mx-2 text-gray-700"><?= $item['quantity'] ?></span>
                                                    <button type="button" onclick="updateQuantity(<?= $item['cart_id'] ?>, <?= $item['quantity'] + 1 ?>)" class="text-gray-500 hover:text-[#2C6E8A] focus:outline-none">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                                <div class="flex items-center">
                                                    <span class="text-[#2C6E8A] font-medium mr-4">₱<?= number_format($item['price'], 2) ?></span>
                                                    <button type="button" onclick="removeItem(<?= $item['cart_id'] ?>)" class="text-red-500 hover:text-red-700 focus:outline-none">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 cart-summary">
                        <h2 class="text-xl font-semibold text-[#2C6E8A] mb-4">Order Summary</h2>
                        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" id="checkout-form">
                            <input type="hidden" name="subtotal" id="subtotal" value="<?= $subtotal ?>">
                            <input type="hidden" name="delivery_fee" id="delivery_fee" value="30.00">
                            <input type="hidden" name="lat" id="lat" value="">
                            <input type="hidden" name="lon" id="lon" value="">
                            <input type="hidden" name="selected_items" id="selected_items" value="">
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="delivery_address" class="block text-sm font-medium text-gray-700 mb-1">Delivery Address</label>
                                    <div class="address-container">
                                        <input type="text" name="delivery_address" id="delivery_address" value="<?= htmlspecialchars($user_address) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-[#2C6E8A] focus:border-[#2C6E8A] pr-24" required>
                                        <button type="button" id="use_current_location" class="location-btn">Use my location</button>
                                        <div id="address_suggestions"></div>
                                    </div>
                                    <div id="map_preview"></div>
                                </div>
                                
                                <div>
                                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                                    <select name="payment_method" id="payment_method" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-[#2C6E8A] focus:border-[#2C6E8A]" required>
                                        <option value="Cash on Delivery (COD)" selected>Cash on Delivery (COD)</option>
                                        <option value="Digital Wallet">Digital Wallet (GCash, PayMaya, etc.)</option>
                                        <option value="Card">Credit/Debit Card</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                                    <input type="text" name="contact_number" id="contact_number" value="<?= htmlspecialchars($user_contact) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-[#2C6E8A] focus:border-[#2C6E8A]" required placeholder="Enter your contact number">
                                </div>
                                
                                <div class="border-t border-gray-200 pt-4">
                                    <div class="flex justify-between mb-2">
                                        <span class="text-gray-600">Subtotal</span>
                                        <span class="font-medium" id="subtotal_display">₱<?= number_format($subtotal, 2) ?></span>
                                    </div>
                        <div class="flex justify-between mb-2">
                                        <span class="text-gray-600">Delivery Fee</span>
                                        <span class="font-medium" id="delivery_fee_display">₱30.00</span>
                        </div>
                        <div class="flex justify-between mb-2">
                                        <span class="text-gray-600">Distance</span>
                                        <span class="font-medium" id="distance_display">Calculating...</span>
                                    </div>
                                    <div class="flex justify-between pt-2 border-t border-gray-200">
                                        <span class="text-lg font-semibold text-[#2C6E8A]">Total</span>
                                        <span class="text-lg font-semibold text-[#2C6E8A]" id="total_display">₱<?= number_format($subtotal + 30.00, 2) ?></span>
                                    </div>
                        </div>
                                
                                <button type="submit" name="checkout" id="checkout-btn" class="w-full bg-[#2C6E8A] text-white py-3 rounded-md hover:bg-[#235A73] transition focus:outline-none focus:ring-2 focus:ring-[#2C6E8A] focus:ring-opacity-50">
                                    Proceed to Checkout
                                </button>
                                <p id="selected-items-notice" class="text-sm text-center text-gray-500 mt-2 hidden">
                                    Only selected items will be checked out
                                </p>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="/public/js/location-service.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize address autocomplete with map preview
            initAddressAutocomplete('delivery_address', 'address_suggestions', 'map_preview');
            
            // Handle checkbox functionality
            const selectAllCheckbox = document.getElementById('select-all');
            const itemCheckboxes = document.querySelectorAll('.cart-item-checkbox');
            const removeSelectedBtn = document.getElementById('remove-selected');
            const checkoutForm = document.getElementById('checkout-form');
            const checkoutBtn = document.getElementById('checkout-btn');
            const selectedItemsNotice = document.getElementById('selected-items-notice');
            const selectedItemsInput = document.getElementById('selected_items');
            const subtotalDisplay = document.getElementById('subtotal_display');
            const totalDisplay = document.getElementById('total_display');
            
            // Initialize remove selected button state
            updateRemoveButtonState();
            
            // Select All checkbox functionality
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const isChecked = this.checked;
                    
                    itemCheckboxes.forEach(checkbox => {
                        checkbox.checked = isChecked;
                    });
                    
                    updateRemoveButtonState();
                    updateCheckoutInfo();
                });
            }
            
            // Individual checkboxes functionality
            itemCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSelectAllCheckboxState();
                    updateRemoveButtonState();
                    updateCheckoutInfo();
                });
            });
                
            // Update checkout form before submission
            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(e) {
                    const selectedIds = getSelectedCartIds();
                    
                    // If no items are selected, show an error
                    if (selectedIds.length === 0) {
                        e.preventDefault();
                    Swal.fire({
                            icon: 'warning',
                            title: 'No Items Selected',
                            text: 'Please select at least one item to checkout.',
                            confirmButtonColor: '#2C6E8A'
                        });
                        return;
                    }
                    
                    // Update the hidden input with selected cart IDs
                    selectedItemsInput.value = JSON.stringify(selectedIds);
                });
            }
            
            // Remove selected items functionality
            if (removeSelectedBtn) {
                removeSelectedBtn.addEventListener('click', function() {
                    const selectedIds = getSelectedCartIds();
                    
                    if (selectedIds.length === 0) {
                    return;
                }
                
                    Swal.fire({
                        title: 'Remove Selected Items',
                        text: 'Are you sure you want to remove the selected items from your cart?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#2C6E8A',
                        cancelButtonColor: '#4a3b2b',
                        confirmButtonText: 'Yes, remove them'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Disable all buttons temporarily
                            const buttons = document.querySelectorAll('button');
                            buttons.forEach(btn => btn.disabled = true);
                            
                            fetch('/controllers/bulk-cart-actions.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    action: 'remove_selected',
                                    cart_ids: selectedIds
                                }),
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Reload the page with a small delay to prevent issues
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 300);
                                } else {
                                    // Re-enable buttons
                                    buttons.forEach(btn => btn.disabled = false);
                                    
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: data.message || 'Failed to remove items',
                                    });
                            }
                        })
                        .catch(error => {
                                // Re-enable buttons
                                buttons.forEach(btn => btn.disabled = false);
                                
                                console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                    text: 'An error occurred. Please try again.',
                                });
                            });
                        }
                    });
                });
            }
            
            // Helper function to get selected cart IDs
            function getSelectedCartIds() {
                const selectedIds = [];
                itemCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        selectedIds.push(parseInt(checkbox.dataset.cartId));
                    }
                });
                return selectedIds;
            }
            
            // Helper function to update "Select All" checkbox state
            function updateSelectAllCheckboxState() {
                if (!selectAllCheckbox) return;
                
                const totalCheckboxes = itemCheckboxes.length;
                const checkedCheckboxes = Array.from(itemCheckboxes).filter(checkbox => checkbox.checked).length;
                
                selectAllCheckbox.checked = totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes;
                selectAllCheckbox.indeterminate = checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes;
            }
            
            // Helper function to update Remove Selected button state
            function updateRemoveButtonState() {
                if (!removeSelectedBtn) return;
                
                const hasCheckedItems = Array.from(itemCheckboxes).some(checkbox => checkbox.checked);
                removeSelectedBtn.disabled = !hasCheckedItems;
            }
            
            // Helper function to update checkout information based on selected items
            function updateCheckoutInfo() {
                if (!subtotalDisplay || !totalDisplay || !selectedItemsNotice) return;
                
                const selectedIds = getSelectedCartIds();
                const allIds = Array.from(itemCheckboxes).map(checkbox => parseInt(checkbox.dataset.cartId));
                
                // If all or none are selected, show the total price
                if (selectedIds.length === 0 || selectedIds.length === allIds.length) {
                    subtotalDisplay.textContent = '₱<?= number_format($subtotal, 2) ?>';
                    totalDisplay.textContent = '₱<?= number_format($subtotal + 30.00, 2) ?>';
                    selectedItemsNotice.classList.add('hidden');
                    return;
                }
                
                // Calculate the subtotal for selected items
                let selectedSubtotal = 0;
                itemCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const price = parseFloat(checkbox.dataset.price);
                        const quantity = parseInt(checkbox.dataset.quantity);
                        selectedSubtotal += price * quantity;
                }
            });
                
                // Update the displays
                subtotalDisplay.textContent = '₱' + selectedSubtotal.toFixed(2);
                totalDisplay.textContent = '₱' + (selectedSubtotal + 30.00).toFixed(2);
                
                // Update the hidden input
                document.getElementById('subtotal').value = selectedSubtotal;
                
                // Show the notice
                selectedItemsNotice.classList.remove('hidden');
            }
            
            <?php if (!empty($error)): ?>
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                    text: '<?= addslashes($error) ?>',
                    confirmButtonColor: '#2C6E8A'
            });
            <?php endif; ?>
        });

        function updateQuantity(cartId, quantity) {
            if (quantity < 1) {
                removeItem(cartId);
                return;
            }
            
            // Disable all buttons temporarily
            const buttons = document.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);
            
            fetch('/controllers/update-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_id: cartId,
                    quantity: quantity
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page with a small delay to prevent issues
                    setTimeout(() => {
                        window.location.reload();
                    }, 300);
                } else {
                    // Re-enable buttons
                    buttons.forEach(btn => btn.disabled = false);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to update quantity',
                    });
                }
            })
            .catch(error => {
                // Re-enable buttons
                buttons.forEach(btn => btn.disabled = false);
                
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.',
                });
            });
        }

        function removeItem(cartId) {
            Swal.fire({
                title: 'Remove Item',
                text: 'Are you sure you want to remove this item from your cart?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2C6E8A',
                cancelButtonColor: '#4a3b2b',
                confirmButtonText: 'Yes, remove it'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Disable all buttons temporarily
                    const buttons = document.querySelectorAll('button');
                    buttons.forEach(btn => btn.disabled = true);
                    
                    fetch('/controllers/remove-cart-item.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            cart_id: cartId
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload the page with a small delay to prevent issues
                            setTimeout(() => {
                                window.location.reload();
                            }, 300);
                        } else {
                            // Re-enable buttons
                            buttons.forEach(btn => btn.disabled = false);
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to remove item',
                            });
                        }
                    })
                    .catch(error => {
                        // Re-enable buttons
                        buttons.forEach(btn => btn.disabled = false);
                        
                        console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                            text: 'An error occurred. Please try again.',
                        });
                });
            }
        });
        }
    </script>
</body>
</html>