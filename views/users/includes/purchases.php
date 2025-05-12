<?php
session_start();
require_once __DIR__ . '../../../../config.php';

// Ensure user is logged in
function ensure_user_logged_in() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /views/auth/login.php");
        exit;
    }
    return $_SESSION['user_id'];
}

// Fetch all orders for the user
function get_user_orders($conn, $user_id) {
    $orders_query = $conn->query("SELECT o.id, o.total_amount, o.status, o.created_at, 
                                 COUNT(oi.id) as item_count, 
                                 MAX(p.status) as payment_status
                                 FROM orders o
                                 LEFT JOIN order_items oi ON o.id = oi.order_id
                                 LEFT JOIN payments p ON o.id = p.order_id
                                 WHERE o.user_id = $user_id
                                 GROUP BY o.id
                                 ORDER BY o.created_at DESC");
    
    $orders = [];
    if ($orders_query && $orders_query->num_rows > 0) {
        while ($row = $orders_query->fetch_assoc()) {
            $orders[] = [
                'id' => $row['id'],
                'total' => $row['total_amount'],
                'status' => $row['status'],
                'date' => $row['created_at'],
                'item_count' => $row['item_count'],
                'payment_status' => $row['payment_status']
            ];
        }
    }
    return $orders;
}

// Fetch specific order details
function get_order_details($conn, $order_id, $user_id) {
    // Verify the order belongs to the user
    $verify_query = $conn->query("SELECT id FROM orders WHERE id = $order_id AND user_id = $user_id");
    if (!$verify_query || $verify_query->num_rows === 0) {
        return null;
    }

    // Get order info
    $order_query = $conn->query("SELECT o.*, r.name as rider_name, r.contact as rider_contact 
                                FROM orders o
                                LEFT JOIN riders r ON o.rider_id = r.id
                                WHERE o.id = $order_id");
    $order = $order_query->fetch_assoc();

    // Get order items
    $items_query = $conn->query("SELECT oi.*, p.item_name, p.item_image 
                                FROM order_items oi
                                JOIN products p ON oi.product_id = p.id
                                WHERE oi.order_id = $order_id");
    $items = [];
    while ($row = $items_query->fetch_assoc()) {
        $items[] = [
            'product_id' => $row['product_id'],
            'name' => htmlspecialchars($row['item_name']),
            'image' => htmlspecialchars($row['item_image']),
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'subtotal' => $row['quantity'] * $row['price']
        ];
    }

    // Get payment info
    $payment_query = $conn->query("SELECT * FROM payments WHERE order_id = $order_id");
    $payment = $payment_query->fetch_assoc();

    return [
        'order' => $order,
        'items' => $items,
        'payment' => $payment
    ];
}

// Main execution
$user_id = ensure_user_logged_in();
$orders = get_user_orders($conn, $user_id);

// Check if viewing a specific order
$order_details = null;
if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $order_details = get_order_details($conn, $order_id, $user_id);
    
    if (!$order_details) {
        header("Location: /views/users/purchases.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Captain's Brew Cafe</title>
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
            --success: #4CAF50;
            --warning: #FFC107;
            --danger: #F44336;
            --info: #2196F3;
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

        .page-title {
            text-align: center;
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 2rem;
            position: relative;
        }

        .page-title::after {
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

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
            font-weight: 500;
        }

        .back-btn:hover {
            color: var(--primary-dark);
        }

        /* Orders List */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .order-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 1.5rem;
            transition: var(--transition);
        }

        .order-card:hover {
            box-shadow: var(--shadow-medium);
            transform: translateY(-3px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-id {
            font-weight: 600;
            color: var(--primary);
        }

        .order-date {
            color: #666;
            font-size: 0.9rem;
        }

        .order-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-pending {
            background-color: #FFF3E0;
            color: #E65100;
        }

        .status-approved {
            background-color: #E8F5E9;
            color: var(--success);
        }

        .status-processing {
            background-color: #E3F2FD;
            color: var(--info);
        }

        .status-assigned {
            background-color: #E0F7FA;
            color: #00ACC1;
        }

        .status-out-for-delivery {
            background-color: #E1F5FE;
            color: #0288D1;
        }

        .status-delivered {
            background-color: #E8F5E9;
            color: var(--success);
        }

        .status-rejected {
            background-color: #FFEBEE;
            color: var(--danger);
        }

        .status-completed {
            background-color: #E8F5E9;
            color: #2E7D32;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .order-total {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .order-items-count {
            color: #666;
            font-size: 0.9rem;
        }

        .view-details-btn {
            background: var(--primary-light);
            color: var(--primary);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .view-details-btn:hover {
            background: var(--primary);
            color: white;
        }

        /* Order Details */
        .order-details {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .order-details-header {
            margin-bottom: 2rem;
        }

        .order-details-title {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .order-details-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .order-meta-item {
            display: flex;
            flex-direction: column;
        }

        .order-meta-label {
            font-size: 0.8rem;
            color: #666;
        }

        .order-meta-value {
            font-weight: 500;
        }

        .order-status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            margin-top: 0.5rem;
        }

        .order-items-list {
            margin: 2rem 0;
        }

        .order-item {
            display: flex;
            gap: 1.5rem;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
            align-items: center;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--border-radius);
        }

        .order-item-details {
            flex: 1;
        }

        .order-item-name {
            font-weight: 500;
            margin-bottom: 0.3rem;
        }

        .order-item-price {
            color: #666;
            font-size: 0.9rem;
        }

        .order-item-quantity {
            color: #666;
            font-size: 0.9rem;
        }

        .order-item-subtotal {
            font-weight: 500;
        }

        .order-summary {
            background: var(--secondary-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .order-summary-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .order-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .order-summary-total {
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #ddd;
        }

        .payment-details {
            margin-top: 2rem;
            background: #F5F5F5;
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }

        .payment-details-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .payment-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }

        .status-pending {
            background-color: #FFF3E0;
            color: #E65100;
        }

        .status-completed {
            background-color: #E8F5E9;
            color: var(--success);
        }

        .status-failed {
            background-color: #FFEBEE;
            color: var(--danger);
        }

        .rider-details {
            margin-top: 2rem;
            background: #E3F2FD;
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }

        .rider-details-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .no-orders {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .order-item {
                flex-direction: column;
                text-align: center;
            }

            .order-item-image {
                margin-bottom: 1rem;
            }

            .order-details-meta {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($order_details): ?>
            <a href="/views/users/purchases.php" class="back-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Back to Orders
            </a>
            
            <div class="order-details">
                <div class="order-details-header">
                    <h1 class="order-details-title">Order #<?= $order_details['order']['id'] ?></h1>
                    <div class="order-details-meta">
                        <div class="order-meta-item">
                            <span class="order-meta-label">Order Date</span>
                            <span class="order-meta-value"><?= date('F j, Y \a\t g:i A', strtotime($order_details['order']['created_at'])) ?></span>
                        </div>
                        <div class="order-meta-item">
                            <span class="order-meta-label">Status</span>
                            <span class="order-status-badge status-<?= strtolower(str_replace(' ', '-', $order_details['order']['status'])) ?>">
                                <?= $order_details['order']['status'] ?>
                            </span>
                        </div>
                        <div class="order-meta-item">
                            <span class="order-meta-label">Delivery Address</span>
                            <span class="order-meta-value"><?= htmlspecialchars($order_details['order']['delivery_address']) ?></span>
                        </div>
                        <div class="order-meta-item">
                            <span class="order-meta-label">Payment Method</span>
                            <span class="order-meta-value"><?= $order_details['order']['payment_method'] ?></span>
                        </div>
                    </div>
                </div>

                <div class="order-items-list">
                    <h2>Order Items</h2>
                    <?php foreach ($order_details['items'] as $item): ?>
                        <div class="order-item">
                            <img src="/public/<?= $item['image'] ?>" alt="<?= $item['name'] ?>" class="order-item-image">
                            <div class="order-item-details">
                                <h3 class="order-item-name"><?= $item['name'] ?></h3>
                                <p class="order-item-price">₱<?= number_format($item['price'], 2) ?></p>
                                <p class="order-item-quantity">Quantity: <?= $item['quantity'] ?></p>
                            </div>
                            <div class="order-item-subtotal">₱<?= number_format($item['subtotal'], 2) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-summary">
                    <h3 class="order-summary-title">Order Summary</h3>
                    <div class="order-summary-row">
                        <span>Subtotal:</span>
                        <span>₱<?= number_format($order_details['order']['total_amount'], 2) ?></span>
                    </div>
                    <div class="order-summary-row">
                        <span>Delivery Fee:</span>
                        <span>₱0.00</span>
                    </div>
                    <div class="order-summary-row order-summary-total">
                        <span>Total:</span>
                        <span>₱<?= number_format($order_details['order']['total_amount'], 2) ?></span>
                    </div>
                </div>

                <div class="payment-details">
                    <h3 class="payment-details-title">Payment Information</h3>
                    <div class="order-summary-row">
                        <span>Payment Method:</span>
                        <span><?= $order_details['payment']['method'] ?></span>
                    </div>
                    <div class="order-summary-row">
                        <span>Amount Paid:</span>
                        <span>₱<?= number_format($order_details['payment']['amount'], 2) ?></span>
                    </div>
                    <div class="order-summary-row">
                        <span>Payment Status:</span>
                        <span>
                            <?= $order_details['payment']['status'] ?>
                            <span class="payment-status status-<?= strtolower($order_details['payment']['status']) ?>">
                                <?= $order_details['payment']['status'] ?>
                            </span>
                        </span>
                    </div>
                </div>

                <?php if ($order_details['order']['rider_id']): ?>
                    <div class="rider-details">
                        <h3 class="rider-details-title">Rider Information</h3>
                        <div class="order-summary-row">
                            <span>Rider Name:</span>
                            <span><?= htmlspecialchars($order_details['order']['rider_name']) ?></span>
                        </div>
                        <div class="order-summary-row">
                            <span>Contact Number:</span>
                            <span><?= htmlspecialchars($order_details['order']['rider_contact']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <h1 class="page-title">My Orders</h1>
            
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>You haven't placed any orders yet.</p>
                    <a href="/" class="view-details-btn" style="margin-top: 1rem;">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <span class="order-id">Order #<?= $order['id'] ?></span>
                                    <span class="order-date"><?= date('F j, Y', strtotime($order['date'])) ?></span>
                                </div>
                                <span class="order-status status-<?= strtolower(str_replace(' ', '-', $order['status'])) ?>">
                                    <?= $order['status'] ?>
                                </span>
                            </div>
                            <div class="order-footer">
                                <div>
                                    <span class="order-total">₱<?= number_format($order['total'], 2) ?></span>
                                    <span class="order-items-count"><?= $order['item_count'] ?> item<?= $order['item_count'] > 1 ? 's' : '' ?></span>
                                </div>
                                <a href="/views/users/purchases.php?order_id=<?= $order['id'] ?>" class="view-details-btn">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>