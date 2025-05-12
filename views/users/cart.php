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
    $cart_query = $conn->query("SELECT c.id, c.product_id, c.quantity, p.item_name, p.item_price, p.item_image 
                               FROM cart c 
                               JOIN products p ON c.product_id = p.id 
                               WHERE c.user_id = $user_id");
    $cart_items = [];
    $total = 0;

    if ($cart_query && $cart_query->num_rows > 0) {
        while ($row = $cart_query->fetch_assoc()) {
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
    }
    return ['items' => $cart_items, 'total' => $total];
}

// Fetch user address
function get_user_address($conn, $user_id) {
    $address_query = $conn->query("SELECT address FROM users WHERE id = $user_id");
    return $address_query->fetch_assoc()['address'] ?? '';
}

// Handle checkout
function handle_checkout($conn, $user_id, $total, $delivery_address, $payment_method) {
    $delivery_address = $conn->real_escape_string($delivery_address);
    $payment_method = $conn->real_escape_string($payment_method);
    
    $conn->begin_transaction();
    try {
        // Insert order
        $order_query = $conn->query("INSERT INTO orders (user_id, total_amount, status, delivery_address, payment_method) 
                                    VALUES ($user_id, $total, 'Pending', '$delivery_address', '$payment_method')");
        if (!$order_query) {
            throw new Exception("Order creation failed: " . $conn->error);
        }
        
        $order_id = $conn->insert_id;
        $cart_items = get_cart_items($conn, $user_id)['items'];
        
        // Move cart items to order_items
        foreach ($cart_items as $item) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            $order_item_query = $conn->query("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                             VALUES ($order_id, $product_id, $quantity, $price)");
            if (!$order_item_query) {
                throw new Exception("Order items insertion failed: " . $conn->error);
            }
        }
        
        // Insert payment
        $payment_query = $conn->query("INSERT INTO payments (order_id, amount, method, status) 
                                      VALUES ($order_id, $total, '$payment_method', 'Pending')");
        if (!$payment_query) {
            throw new Exception("Payment creation failed: " . $conn->error);
        }
        
        // Clear cart
        $conn->query("DELETE FROM cart WHERE user_id = $user_id");
        
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
$total = $cart_data['total'];
$user_address = get_user_address($conn, $user_id);

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $delivery_address = $_POST['delivery_address'];
    $payment_method = $_POST['payment_method'];
    
    $result = handle_checkout($conn, $user_id, $total, $delivery_address, $payment_method);
    if ($result['success']) {
        header("Location: /views/users/purchases.php?order_id=" . $result['order_id']);
        exit;
    } else {
        $error = $result['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Cart - Captain's Brew Cafe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2C6E8A;
            --primary-dark: #235A73;
            --primary-light: #A9D6E5;
            --secondary: #4a3b2b;
            --secondary-light: #FFFAEE;
            --secondary-lighter: #FFDBB5;
            --white: #fff;
            --shadow-light: 0 2px 5px rgba(74, 59, 43, 0.2);
            --shadow-medium: 0 4px 8px rgba(44, 110, 138, 0.2);
            --shadow-dark: 0 5px 15px rgba(74, 59, 43, 0.5);
            --border-radius: 10px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: var(--white);
            color: var(--secondary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .cart-title {
            text-align: center;
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 2rem;
            position: relative;
        }

        .cart-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary-light);
            border-radius: 3px;
        }

        .cart-item {
            display: flex;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 1rem;
            margin-bottom: 1rem;
            align-items: center;
        }

        .cart-item-image {
            width: 100px;
            height: auto;
            border-radius: var(--border-radius);
            margin-right: 1rem;
        }

        .cart-item-content {
            flex: 1;
        }

        .cart-item-title {
            font-size: 1.2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .cart-item-price {
            font-size: 1rem;
            color: var(--secondary);
            font-weight: 500;
        }

        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
        }

        .quantity-btn {
            background: var(--primary-light);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .quantity-btn:hover {
            background: var(--primary);
            color: white;
        }

        .cart-item-quantity input {
            width: 50px;
            text-align: center;
            padding: 0.3rem;
            border: 2px solid var(--primary-light);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
        }

        .remove-btn {
            background: #d32f2f;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .remove-btn:hover {
            background: #b71c1c;
        }

        .cart-summary {
            background: var(--secondary-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: var(--shadow-light);
        }

        .cart-summary h3 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .cart-summary p {
            font-size: 1.1rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .checkout-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            margin-top: 1rem;
        }

        .checkout-btn:hover {
            background: var(--primary-dark);
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }

        .modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 2rem;
            position: relative;
            box-shadow: var(--shadow-dark);
        }

        .close-modal {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--secondary);
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--primary);
        }

        .modal-body {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .modal-body h2 {
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .modal-body label {
            font-size: 1rem;
            color: var(--secondary);
            margin-bottom: 0.3rem;
            display: block;
        }

        .modal-body input,
        .modal-body select,
        .modal-body textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--primary-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            color: var(--secondary);
            transition: var(--transition);
            margin-bottom: 1rem;
        }

        .modal-body input:focus,
        .modal-body select:focus,
        .modal-body textarea:focus {
            border-color: var(--primary);
            box-shadow: var(--shadow-medium);
        }

        .error-message {
            background: #ffe0e0;
            color: #d32f2f;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin: 1rem 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                text-align: center;
            }

            .cart-item-image {
                margin: 0 0 1rem 0;
            }

            .cart-item-content {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="cart-title">Your Cart</h1>

        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="no-items">Your cart is empty.</div>
        <?php else: ?>
            <!-- Cart Items -->
            <?php foreach ($cart_items as $item): ?>
                <div class="cart-item" data-cart-id="<?= $item['cart_id'] ?>">
                    <img src="/public/<?= $item['image'] ?>" alt="<?= $item['name'] ?>" class="cart-item-image">
                    <div class="cart-item-content">
                        <h2 class="cart-item-title"><?= $item['name'] ?></h2>
                        <p class="cart-item-price">₱<?= number_format($item['price'], 2) ?> x <?= $item['quantity'] ?> = ₱<?= number_format($item['subtotal'], 2) ?></p>
                        <div class="cart-item-quantity">
                            <button class="quantity-btn minus">-</button>
                            <input type="number" value="<?= $item['quantity'] ?>" min="1" max="10">
                            <button class="quantity-btn plus">+</button>
                        </div>
                    </div>
                    <button class="remove-btn">Remove</button>
                </div>
            <?php endforeach; ?>

            <!-- Cart Summary -->
            <div class="cart-summary">
                <h3>Order Summary</h3>
                <p>Total: ₱<?= number_format($total, 2) ?></p>
                <button class="checkout-btn" onclick="showCheckoutModal()">Proceed to Checkout</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">×</span>
            <div class="modal-body">
                <h2>Checkout</h2>
                <form id="checkoutForm" method="POST" action="">
                    <label for="delivery_address">Delivery Address</label>
                    <textarea id="delivery_address" name="delivery_address" required><?= htmlspecialchars($user_address) ?></textarea>

                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="Card">Card</option>
                        <option value="COD">Cash on Delivery</option>
                        <option value="Digital Wallet">Digital Wallet</option>
                    </select>

                    <button type="submit" name="checkout" class="checkout-btn">Confirm Order</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Quantity Update
        document.querySelectorAll('.quantity-btn').forEach(button => {
            button.addEventListener('click', function() {
                const cartItem = this.closest('.cart-item');
                const cartId = cartItem.dataset.cartId;
                const input = cartItem.querySelector('input');
                let quantity = parseInt(input.value);

                if (this.classList.contains('minus') && quantity > 1) {
                    quantity--;
                } else if (this.classList.contains('plus') && quantity < 10) {
                    quantity++;
                }

                input.value = quantity;

                // Update cart in database
                fetch('/includes/update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cart_id=${cartId}&quantity=${quantity}`
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          location.reload(); // Reload to update subtotal and total
                      } else {
                          alert('Failed to update quantity: ' + data.error);
                      }
                  });
            });
        });

        // Remove Item
        document.querySelectorAll('.remove-btn').forEach(button => {
            button.addEventListener('click', function() {
                const cartItem = this.closest('.cart-item');
                const cartId = cartItem.dataset.cartId;

                fetch('/includes/remove_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cart_id=${cartId}`
                }).then(response => response.json())
                  .then(data => {
                      if (data.success) {
                          location.reload();
                      } else {
                          alert('Failed to remove item: ' + data.error);
                      }
                  });
            });
        });

        // Checkout Modal
        function showCheckoutModal() {
            document.getElementById('checkoutModal').classList.add('active');
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').classList.remove('active');
        }

        document.querySelector('.close-modal').addEventListener('click', closeCheckoutModal);
        document.getElementById('checkoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCheckoutModal();
            }
        });
    </script>
</body>
</html>