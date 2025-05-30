<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/rider_auth.php';

// Ensure rider is logged in
if (!isRiderLoggedIn()) {
    header('Location: /views/riders/login.php');
    exit();
}

$rider_id = getCurrentRiderId();

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$order_id) {
    header('Location: /views/riders/Rider-Dashboard.php');
    exit();
}

// Fetch order details
$sql = "SELECT o.*, u.username, u.contact 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ? AND o.rider_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $order_id, $rider_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Order not found or doesn't belong to this rider
    header('Location: /views/riders/Rider-Dashboard.php');
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// Fetch order items
$sql = "SELECT oi.*, p.item_name, p.item_image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$items = [];

while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}
$stmt->close();

// Generate CSRF token for form submission
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order_id ?> Details - Rider Dashboard</title>
    <link rel="icon" href="/public/images/LOGO.png" sizes="any">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Leaflet CSS for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f6f5;
            color: #4a3b2b;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .header {
            background: linear-gradient(135deg, #FFFAEE, #FFDBB5);
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .logo {
            display: block;
            margin: 0 auto;
            height: 60px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            background-color: #2C6E8A;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            margin-bottom: 1rem;
            transition: background-color 0.3s;
        }
        
        .back-btn:hover {
            background-color: #235A73;
        }
        
        h1 {
            font-size: 1.8rem;
            color: #2C6E8A;
            margin-bottom: 1.5rem;
        }
        
        .order-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .order-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .order-details, .customer-details, .items-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.2rem;
            color: #2C6E8A;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 0.75rem;
        }
        
        .detail-label {
            font-weight: 500;
            width: 150px;
            color: #4a3b2b;
        }
        
        .detail-value {
            flex: 1;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-assigned {
            background-color: #FFF3CD;
            color: #856404;
        }
        
        .status-out-for-delivery {
            background-color: #D1ECF1;
            color: #0C5460;
        }
        
        .status-delivered {
            background-color: #D4EDDA;
            color: #155724;
        }
        
        .items-list {
            list-style: none;
        }
        
        .item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 1rem;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .item-meta {
            font-size: 0.85rem;
            color: #666;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: #2C6E8A;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #235A73;
        }
        
        .btn-success {
            background-color: #28A745;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-disabled {
            background-color: #6C757D;
            color: white;
            cursor: not-allowed;
        }
        
        #map-container {
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <img src="/public/images/LOGO.png" alt="Captain's Brew Logo" class="logo">
        </div>
    </header>
    
    <div class="container">
        <a href="/views/riders/Rider-Dashboard.php" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16" style="margin-right: 0.5rem;">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
            </svg>
            Back to Dashboard
        </a>
        
        <h1>Order #<?= $order_id ?> Details</h1>
        
        <div class="order-grid">
            <div class="left-column">
                <div class="order-details">
                    <h2 class="section-title">Order Information</h2>
                    <div class="detail-row">
                        <div class="detail-label">Order ID:</div>
                        <div class="detail-value">#<?= $order_id ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Order Date:</div>
                        <div class="detail-value"><?= date('F j, Y', strtotime($order['created_at'])) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Order Time:</div>
                        <div class="detail-value"><?= date('g:i A', strtotime($order['created_at'])) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Status:</div>
                        <div class="detail-value">
                            <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $order['status'])) ?>">
                                <?= $order['status'] ?>
                            </span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Payment Method:</div>
                        <div class="detail-value"><?= $order['payment_method'] ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Total Amount:</div>
                        <div class="detail-value">₱<?= number_format($order['total_amount'], 2) ?></div>
                    </div>
                </div>
                
                <div class="customer-details">
                    <h2 class="section-title">Customer Information</h2>
                    <div class="detail-row">
                        <div class="detail-label">Name:</div>
                        <div class="detail-value"><?= htmlspecialchars($order['username']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Contact:</div>
                        <div class="detail-value"><?= htmlspecialchars($order['contact_number'] ? $order['contact_number'] : $order['contact']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Delivery Address:</div>
                        <div class="detail-value"><?= htmlspecialchars($order['delivery_address']) ?></div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <?php if ($order['status'] === 'Assigned'): ?>
                        <button id="start-delivery-btn" class="btn btn-primary">Start Delivery</button>
                    <?php elseif ($order['status'] === 'Out for Delivery'): ?>
                        <button id="mark-delivered-btn" class="btn btn-success">Mark as Delivered</button>
                    <?php else: ?>
                        <button class="btn btn-disabled" disabled>Order <?= $order['status'] ?></button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="right-column">
                <div id="map-container"></div>
                
                <div class="items-container">
                    <h2 class="section-title">Order Items</h2>
                    <ul class="items-list">
                        <?php foreach ($items as $item): ?>
                            <li class="item">
                                <?php 
                                $image_path = !empty($item['item_image']) ? '/public/' . ltrim($item['item_image'], '/') : '/public/images/placeholder.jpg';
                                ?>
                                <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($item['item_name']) ?>" class="item-image" onerror="this.src='/public/images/placeholder.jpg';">
                                <div class="item-details">
                                    <div class="item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                                    <div class="item-meta">
                                        <?php if (!empty($item['variation'])): ?>
                                            <span><?= htmlspecialchars($item['variation']) ?></span> •
                                        <?php endif; ?>
                                        <span>Qty: <?= $item['quantity'] ?></span> •
                                        <span>₱<?= number_format($item['price'], 2) ?></span>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/public/js/location-service.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map if coordinates are available
            <?php if (!empty($order['lat']) && !empty($order['lon'])): ?>
                const orderLocation = {
                    lat: <?= $order['lat'] ?>,
                    lon: <?= $order['lon'] ?>
                };
                
                initDeliveryMap('map-container', orderLocation);
            <?php else: ?>
                document.getElementById('map-container').innerHTML = '<div style="padding: 2rem; text-align: center; color: #666;">No location data available for this order.</div>';
            <?php endif; ?>
            
            // Handle status update buttons
            const startDeliveryBtn = document.getElementById('start-delivery-btn');
            const markDeliveredBtn = document.getElementById('mark-delivered-btn');
            
            if (startDeliveryBtn) {
                startDeliveryBtn.addEventListener('click', function() {
                    updateOrderStatus('Out for Delivery');
                });
            }
            
            if (markDeliveredBtn) {
                markDeliveredBtn.addEventListener('click', function() {
                    updateOrderStatus('Delivered');
                });
            }
            
            function updateOrderStatus(status) {
                // Validate status transition
                <?php if ($order['status'] === 'Assigned'): ?>
                if (status === 'Delivered') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Status Change',
                        text: 'Order must be in Out for Delivery status to mark as delivered',
                    });
                    return;
                }
                <?php elseif ($order['status'] === 'Out for Delivery'): ?>
                if (status === 'Assigned') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Status Change',
                        text: 'Cannot change status back to Assigned',
                    });
                    return;
                }
                <?php else: ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Status Change',
                    text: 'This order cannot be updated',
                });
                return;
                <?php endif; ?>
                
                Swal.fire({
                    title: 'Update Order Status',
                    text: `Are you sure you want to mark this order as ${status}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, update it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('/controllers/rider-update-order.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                order_id: <?= $order_id ?>,
                                status: status,
                                csrf_token: '<?= $csrf_token ?>'
                            }),
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Status Updated',
                                    text: `Order has been marked as ${status}`,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Failed to update order status'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred. Please try again.'
                            });
                        });
                    }
                });
            }
        });
    </script>
</body>
</html> 