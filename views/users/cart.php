<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /views/auth/login.php");
    exit();
}

// Database connection
$db_host = 'localhost';
$db_user = 'root'; 
$db_pass = ''; 
$db_name = 'cafe_db';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_cart'])) {
        // Update item quantities
        foreach ($_POST['quantity'] as $item_id => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity > 0) {
                $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("iii", $quantity, $item_id, $_SESSION['user_id']);
                $stmt->execute();
            } else {
                // Remove item if quantity is 0
                $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ii", $item_id, $_SESSION['user_id']);
                $stmt->execute();
            }
        }
    } elseif (isset($_POST['remove_item'])) {
        // Remove specific item
        $item_id = (int)$_POST['item_id'];
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $item_id, $_SESSION['user_id']);
        $stmt->execute();
    } elseif (isset($_POST['checkout'])) {
        // Process checkout
        header("Location: /views/users/checkout.php");
        exit();
    }
}

// Get cart items
$stmt = $conn->prepare("
    SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image 
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.08; // Example 8% tax
$total = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Captain's Brew Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #2C6E8A;
            --primary-dark: #235A73;
            --primary-light: #A9D6E5;
            --secondary: #4a3b2b;
            --secondary-light: #FFFAEE;
            --secondary-lighter: #FFDBB5;
            --accent: #ffb74a;
            --white: #fff;
            --dark: #1a1310;
            --text: #333333;
            --shadow-light: 0 2px 5px rgba(74, 59, 43, 0.2);
            --shadow-medium: 0 4px 8px rgba(44, 110, 138, 0.2);
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
            background-color: var(--secondary-light);
            color: var(--text);
            padding-top: 80px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        h1, h2, h3 {
            font-family: 'Playfair Display', serif;
            color: var(--primary);
        }

        .cart-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .cart-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .cart-container {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
        }

        .cart-items {
            flex: 2;
            min-width: 300px;
        }

        .cart-summary {
            flex: 1;
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            height: fit-content;
        }

        .cart-item {
            display: flex;
            background: var(--white);
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-light);
        }

        .cart-item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
        }

        .cart-item-details {
            flex: 1;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .cart-item-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .cart-item-price {
            color: var(--secondary);
            font-weight: 500;
        }

        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .quantity-input {
            width: 60px;
            padding: 0.5rem;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
        }

        .remove-btn {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            transition: var(--transition);
        }

        .remove-btn:hover {
            color: var(--primary-dark);
        }

        .summary-title {
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .summary-total {
            font-weight: 600;
            font-size: 1.2rem;
            margin: 1.5rem 0;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .checkout-btn {
            width: 100%;
            padding: 1rem;
            background-color: var(--accent);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .checkout-btn:hover {
            background-color: var(--primary);
            transform: translateY(-2px);
        }

        .empty-cart {
            text-align: center;
            padding: 3rem;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
        }

        .empty-cart p {
            margin-bottom: 1.5rem;
        }

        .continue-shopping {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary);
            color: white;
            border-radius: var(--border-radius);
            text-decoration: none;
            transition: var(--transition);
        }

        .continue-shopping:hover {
            background-color: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .cart-container {
                flex-direction: column;
            }
            
            .cart-item {
                flex-direction: column;
            }
            
            .cart-item-image {
                width: 100%;
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Header would be included from a separate file -->
    <?php include_once '../partials/header.php'; ?>

    <div class="container">
        <div class="cart-header">
            <h1>Your Cart</h1>
            <p>Review your items before checkout</p>
        </div>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items yet</p>
                <a href="/views/menu.php" class="continue-shopping">Browse Menu</a>
            </div>
        <?php else: ?>
            <form method="POST" class="cart-container">
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <img src="/public/images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="cart-item-image">
                            <div class="cart-item-details">
                                <div>
                                    <h3 class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p class="cart-item-price">$<?php echo number_format($item['price'], 2); ?></p>
                                </div>
                                <div class="cart-item-actions">
                                    <input type="number" 
                                           name="quantity[<?php echo $item['id']; ?>]" 
                                           class="quantity-input" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1">
                                    <button type="submit" 
                                            name="remove_item" 
                                            class="remove-btn"
                                            onclick="return confirm('Remove this item from your cart?')">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" name="update_cart" class="checkout-btn" style="margin-top: 1rem;">
                        Update Cart
                    </button>
                </div>

                <div class="cart-summary">
                    <h2 class="summary-title">Order Summary</h2>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (8%)</span>
                        <span>$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <button type="submit" name="checkout" class="checkout-btn">
                        Proceed to Checkout
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Footer would be included from a separate file -->
    <?php include_once '../partials/footer.php'; ?>

    <script>
        // Quantity input validation
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                if (this.value < 1) {
                    this.value = 1;
                }
            });
        });
    </script>
</body>
</html>