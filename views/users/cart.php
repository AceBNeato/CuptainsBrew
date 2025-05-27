<?php
session_start();
require_once __DIR__ . '../../../config.php';

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
    $sql = "SELECT c.id, c.product_id, c.quantity, p.item_name, p.item_price, p.item_image 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart_items = [];
    $total = 0;

    while ($row = $result->fetch_assoc()) {
        $subtotal = $row['quantity'] * $row['item_price'];
        $total += $subtotal;
        $cart_items[] = [
            'cart_id' => $row['id'],
            'product_id' => $row['product_id'],
            'name' => htmlspecialchars($row['item_name']),
            'price' => $row['item_price'],
            'quantity' => $row['quantity'],
            'image' => htmlspecialchars($row['item_image']),
            'subtotal' => $subtotal
        ];
    }
    $stmt->close();
    return ['items' => $cart_items, 'total' => $total];
}

// Fetch user address
function get_user_address($conn, $user_id) {
    $sql = "SELECT address FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $address = $result->fetch_assoc()['address'] ?? '';
    $stmt->close();
    return $address;
}

// Handle checkout
define('DELIVERY_FEE', 50.00); // Configurable delivery fee

function handle_checkout($conn, $user_id, $subtotal, $delivery_address, $payment_method) {
    $total = $subtotal + DELIVERY_FEE;
    $delivery_address = $conn->real_escape_string($delivery_address);
    $payment_method = $conn->real_escape_string($payment_method);
    
    $conn->begin_transaction();
    try {
        $sql = "INSERT INTO orders (user_id, total_amount, status, delivery_address, payment_method) 
                VALUES (?, ?, 'Pending', ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('idss', $user_id, $total, $delivery_address, $payment_method);
        if (!$stmt->execute()) {
            throw new Exception("Order creation failed: " . $stmt->error);
        }
        $order_id = $conn->insert_id;
        $stmt->close();
        
        $cart_items = get_cart_items($conn, $user_id)['items'];
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        foreach ($cart_items as $item) {
            $stmt->bind_param('iiid', $order_id, $item['product_id'], $item['quantity'], $item['price']);
            if (!$stmt->execute()) {
                throw new Exception("Order items insertion failed: " . $stmt->error);
            }
        }
        $stmt->close();
        
        $sql = "INSERT INTO payments (order_id, amount, method, status) VALUES (?, ?, ?, 'Pending')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ids', $order_id, $total, $payment_method);
        if (!$stmt->execute()) {
            throw new Exception("Payment creation failed: " . $stmt->error);
        }
        $stmt->close();
        
        $sql = "DELETE FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        return ['success' => true, 'order_id' => $order_id];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => "Checkout failed: " . $e->getMessage()];
    }
}

// Main execution
$user_id = ensure_user_logged_in();
$cart_data = get_cart_items($conn, $user_id);
$cart_items = $cart_data['items'];
$subtotal = $cart_data['total'];
$total = $subtotal + DELIVERY_FEE;
$user_address = get_user_address($conn, $user_id);

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $delivery_address = $_POST['delivery_address'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    
    if (empty($delivery_address) || empty($payment_method)) {
        $error = "Delivery address and payment method are required.";
    } else {
        $result = handle_checkout($conn, $user_id, $subtotal, $delivery_address, $payment_method);
        if ($result['success']) {
            header("Location: /views/users/User-Purchase.php?order_id=" . $result['order_id']);
            exit;
        } else {
            $error = $result['error'];
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            <a href="/views/users/User-Home.php" class="text-[#2C6E8A] hover:text-[#235A73] flex items-center text-lg font-medium">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Home
            </a>
        </div>

        <h1 class="text-3xl font-bold text-[#2C6E8A] text-center mb-8">Your Cart</h1>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6 text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="text-center text-[#4A3B2B] text-lg italic bg-[#A9D6E5] p-6 rounded-lg">
                Your cart is empty. Start shopping now!
            </div>
        <?php else: ?>
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Cart Items -->
                <div class="lg:w-2/3">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item bg-white rounded-lg shadow p-4 mb-4 flex flex-col sm:flex-row items-center" data-cart-id="<?= $item['cart_id'] ?>">
                            <img src="/public/<?= $item['image'] ?>" alt="<?= $item['name'] ?>" class="w-24 h-24 object-cover rounded-lg mr-4">
                            <div class="flex-1">
                                <h2 class="text-lg font-semibold text-[#2C6E8A]"><?= $item['name'] ?></h2>
                                <p class="text-[#4A3B2B]">₱<?= number_format($item['price'], 2) ?> x <?= $item['quantity'] ?> = ₱<span class="item-subtotal"><?= number_format($item['subtotal'], 2) ?></span></p>
                                <div class="flex items-center mt-2">
                                    <button class="quantity-btn minus bg-[#A9D6E5] text-[#2C6E8A] w-8 h-8 rounded-full flex items-center justify-center hover:bg-[#2C6E8A] hover:text-white">-</button>
                                    <input type="number" value="<?= $item['quantity'] ?>" min="1" max="10" class="quantity-input w-16 text-center mx-2 border-2 border-[#A9D6E5] rounded-lg p-1" readonly>
                                    <button class="quantity-btn plus bg-[#A9D6E5] text-[#2C6E8A] w-8 h-8 rounded-full flex items-center justify-center hover:bg-[#2C6E8A] hover:text-white">+</button>
                                </div>
                            </div>
                            <button class="remove-btn bg-red-500 text-white px-4 py-2 rounded-lg mt-2 sm:mt-0 hover:bg-red-600">Remove</button>
                        </div>
                    <?php endforeach; ?>
                    <button id="clear-cart-btn" class="bg-gray-500 text-white px-4 py-2 rounded-lg mt-4 hover:bg-gray-600">Clear Cart</button>
                </div>

                <!-- Order Summary -->
                <div class="lg:w-1/3">
                    <div class="cart-summary bg-[#FFFAEE] rounded-lg shadow p-6">
                        <h3 class="text-xl font-semibold text-[#2C6E8A] mb-4">Order Summary</h3>
                        <div class="flex justify-between mb-2">
                            <span>Subtotal</span>
                            <span id="cart-subtotal">₱<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span>Delivery Fee</span>
                            <span id="delivery-fee">₱<?= number_format(DELIVERY_FEE, 2) ?></span>
                        </div>
                        <div class="flex justify-between font-bold text-lg">
                            <span>Total</span>
                            <span id="cart-total">₱<?= number_format($total, 2) ?></span>
                        </div>
                        <button class="checkout-btn bg-[#2C6E8A] text-white w-full py-3 rounded-lg mt-4 hover:bg-[#235A73] transition" onclick="showCheckoutModal()">Proceed to Checkout</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="modal fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="modal-content bg-white rounded-lg p-6 w-full max-w-md animate-slide-in">
            <span class="close-modal text-[#4A3B2B] text-2xl absolute top-4 right-4 cursor-pointer hover:text-[#2C6E8A]">×</span>
            <h2 class="text-2xl font-semibold text-[#2C6E8A] mb-4">Checkout</h2>
            <form id="checkoutForm" method="POST" action="">
                <div class="mb-4">
                    <label for="delivery_address" class="block text-[#4A3B2B] mb-2">Delivery Address</label>
                    <textarea id="delivery_address" name="delivery_address" required class="w-full p-2 border-2 border-[#A9D6E5] rounded-lg focus:border-[#2C6E8A] focus:ring-2 focus:ring-[#2C6E8A]"><?= htmlspecialchars($user_address) ?></textarea>
                </div>
                <div class="mb-4">
                    <label for="payment_method" class="block text-[#4A3B2B] mb-2">Payment Method</label>
                    <select id="payment_method" name="payment_method" required class="w-full p-2 border-2 border-[#A9D6E5] rounded-lg focus:border-[#2C6E8A] focus:ring-2 focus:ring-[#2C6E8A]">
                        <option value="Card">Card</option>
                        <option value="COD">Cash on Delivery</option>
                        <option value="Digital Wallet">Digital Wallet</option>
                    </select>
                </div>
                <button type="submit" name="checkout" class="bg-[#2C6E8A] text-white w-full py-3 rounded-lg hover:bg-[#235A73] transition">Confirm Order</button>
            </form>
        </div>
    </div>

    <script>
        // Format currency
        const formatCurrency = (amount) => `₱${parseFloat(amount).toFixed(2)}`;

        // Update cart totals
        const updateCartTotals = () => {
            let subtotal = 0;
            document.querySelectorAll('.cart-item').forEach(item => {
                const quantity = parseInt(item.querySelector('.quantity-input').value);
                const price = parseFloat(item.querySelector('p').textContent.match(/₱([\d.]+)/)[1]); // Adjusted selector
                const itemSubtotal = quantity * price;
                item.querySelector('.item-subtotal').textContent = formatCurrency(itemSubtotal).replace('₱', '');
                subtotal += itemSubtotal;
            });
            const deliveryFee = <?= DELIVERY_FEE ?>;
            const total = subtotal + deliveryFee;
            document.getElementById('cart-subtotal').textContent = formatCurrency(subtotal);
            document.getElementById('delivery-fee').textContent = formatCurrency(deliveryFee);
            document.getElementById('cart-total').textContent = formatCurrency(total);
        };

        // Quantity Update
        document.querySelectorAll('.quantity-btn').forEach(button => {
            button.addEventListener('click', async () => {
                const cartItem = button.closest('.cart-item');
                const cartId = cartItem.dataset.cartId;
                const input = cartItem.querySelector('.quantity-input');
                let quantity = parseInt(input.value);

                if (button.classList.contains('minus') && quantity > 1) {
                    quantity--;
                } else if (button.classList.contains('plus') && quantity < 10) {
                    quantity++;
                }

                input.value = quantity;

                try {
                    const response = await fetch('/views/users/includes/update-cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `cart_id=${encodeURIComponent(cartId)}&quantity=${encodeURIComponent(quantity)}`
                    });
                    const data = await response.json();
                    if (data.success) {
                        updateCartTotals(); // Update totals dynamically
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || 'Failed to update quantity'
                        });
                        input.value = data.quantity || quantity; // Revert on error
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error updating quantity: ' + error.message
                    });
                    input.value = quantity; // Revert on error
                }
            });
        });

        // Remove Item
        document.querySelectorAll('.remove-btn').forEach(button => {
            button.addEventListener('click', async () => {
                const cartItem = button.closest('.cart-item');
                const cartId = cartItem.dataset.cartId;

                const result = await Swal.fire({
                    title: 'Remove Item',
                    text: 'Are you sure you want to remove this item from your cart?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, remove it!',
                    cancelButtonText: 'Cancel'
                });

                if (result.isConfirmed) {
                    try {
                        const response = await fetch('/views/users/includes/remove-cart.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `cart_id=${encodeURIComponent(cartId)}`
                        });
                        const data = await response.json();
                        if (data.success) {
                            cartItem.remove();
                            updateCartTotals(); // Update totals after removal
                            if (!document.querySelector('.cart-item')) {
                                location.reload(); // Refresh to show empty cart message
                            }
                            Swal.fire({
                                icon: 'success',
                                title: 'Item Removed',
                                text: 'Item has been removed from your cart.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.error || 'Failed to remove item'
                            });
                        }
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error removing item: ' + error.message
                        });
                    }
                }
            });
        });

        // Clear Cart
        document.getElementById('clear-cart-btn').addEventListener('click', async () => {
            const result = await Swal.fire({
                title: 'Clear Cart',
                text: 'Are you sure you want to remove all items from your cart?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, clear it!',
                cancelButtonText: 'Cancel'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch('/views/users/includes/remove-cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `user_id=<?= $user_id ?>&action=clear`
                    });
                    const data = await response.json();
                    if (data.success) {
                        location.reload();
                        Swal.fire({
                            icon: 'success',
                            title: 'Cart Cleared',
                            text: 'All items have been removed from your cart.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.error || 'Failed to clear cart'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error clearing cart: ' + error.message
                    });
                }
            }
        });

        // Checkout Modal
        function showCheckoutModal() {
            if (document.querySelectorAll('.cart-item').length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Empty Cart',
                    text: 'Your cart is empty. Add items before checking out.'
                });
                return;
            }
            document.getElementById('checkoutModal').classList.remove('hidden');
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').classList.add('hidden');
        }

        document.querySelector('.close-modal').addEventListener('click', closeCheckoutModal);
        document.getElementById('checkoutModal').addEventListener('click', (e) => {
            if (e.target === document.getElementById('checkoutModal')) {
                closeCheckoutModal();
            }
        });

        // Validate checkout form
        document.getElementById('checkoutForm').addEventListener('submit', (e) => {
            const address = document.getElementById('delivery_address').value.trim();
            const paymentMethod = document.getElementById('payment_method').value;
            if (!address || !paymentMethod) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please fill in all required fields'
                });
            }
        });

        // Initial cart total calculation
        updateCartTotals();
    </script>
</body>
</html>
</html>