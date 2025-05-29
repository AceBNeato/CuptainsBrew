<?php
session_start();
global $conn;
require_once __DIR__ . '/../../config.php';

// Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: /views/users/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$orders = [];
$single_order = null;

// Check if viewing a specific order
if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    
    // Get order details with better error handling for missing columns
    try {
        // First check if the delivery_fee column exists
        $columnCheck = $conn->query("SHOW COLUMNS FROM orders LIKE 'delivery_fee'");
        $hasDeliveryFee = $columnCheck->num_rows > 0;
        
        $sql = "SELECT 
            o.id,
            o.created_at,
            DATE(o.created_at) AS order_date,
            TIME(o.created_at) AS order_time,
            o.status,
            o.total_amount,
            o.delivery_address,
            o.payment_method,
            o.cancellation_reason,";
            
        if ($hasDeliveryFee) {
            $sql .= "o.delivery_fee,";
        } else {
            $sql .= "30.00 as delivery_fee,";
        }
        
        // Check if lat/lon columns exist
        $latLonCheck = $conn->query("SHOW COLUMNS FROM orders LIKE 'lat'");
        $hasLatLon = $latLonCheck->num_rows > 0;
        
        if ($hasLatLon) {
            $sql .= "o.lat, o.lon,";
        } else {
            $sql .= "NULL as lat, NULL as lon,";
        }
        
        $sql .= "r.name AS rider_name,
            r.contact AS rider_contact
        FROM orders o
        LEFT JOIN riders r ON o.rider_id = r.id
        WHERE o.id = ? AND o.user_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $single_order = $result->fetch_assoc();
            $single_order['items'] = [];
            
            // Get order items
            $sql_items = "SELECT 
                oi.quantity,
                oi.price,
                oi.variation,
                p.item_name,
                p.item_image
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?";
            
            $stmt_items = $conn->prepare($sql_items);
            $stmt_items->bind_param('i', $order_id);
            $stmt_items->execute();
            $items_result = $stmt_items->get_result();
            
            if ($items_result && $items_result->num_rows > 0) {
                while ($item = $items_result->fetch_assoc()) {
                    $single_order['items'][] = $item;
                }
            }
            $stmt_items->close();
        }
        $stmt->close();
    } catch (Exception $e) {
        // Handle error
        error_log("Error in User-Purchase.php: " . $e->getMessage());
        // Display user-friendly error message
        echo '<div class="alert alert-danger">
                <p>Sorry, we couldn\'t retrieve your order details. Please try again or contact customer support.</p>
              </div>';
    }
} else {
// Get filter status from GET parameter
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : 'All';
$valid_statuses = ['All', 'Pending', 'Approved', 'Rejected', 'Delivered', 'Out for Delivery', 'Cancelled'];
if (!in_array($filter_status, $valid_statuses)) {
    $filter_status = 'All';
}

// Prepare SQL query with optional status filter
$sql = "SELECT 
    o.id,
    o.created_at,
    DATE(o.created_at) AS order_date,
    TIME(o.created_at) AS order_time,
    o.status,
    o.total_amount,
    o.delivery_address,
    o.payment_method,
    o.cancellation_reason,
    r.name AS rider_name
FROM orders o
    LEFT JOIN riders r ON o.rider_id = r.id
WHERE o.user_id = ?";
if ($filter_status !== 'All') {
    $sql .= " AND o.status = ?";
}
    $sql .= " ORDER BY o.created_at DESC";
    
    try {
$stmt = $conn->prepare($sql);
if ($filter_status === 'All') {
    $stmt->bind_param('i', $user_id);
} else {
    $stmt->bind_param('is', $user_id, $filter_status);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[$row['id']] = [
            'id' => $row['id'],
            'date' => $row['order_date'],
            'time' => $row['order_time'],
            'status' => $row['status'],
            'total_amount' => $row['total_amount'],
            'delivery_address' => $row['delivery_address'],
            'payment_method' => $row['payment_method'],
            'rider_name' => $row['rider_name'],
            'cancellation_reason' => $row['cancellation_reason'],
            'items' => []
        ];
    }

    // Check if order_cancellations table exists and fetch detailed cancellation info
    $table_check = $conn->query("SHOW TABLES LIKE 'order_cancellations'");
    if ($table_check && $table_check->num_rows > 0 && !empty($orders)) {
        // Check if is_admin column exists
        $column_check = $conn->query("SHOW COLUMNS FROM order_cancellations LIKE 'is_admin'");
        $has_is_admin = $column_check && $column_check->num_rows > 0;
        
        // Make sure we have order IDs before trying to implode
        $order_ids = array_keys($orders);
        if (!empty($order_ids)) {
            $order_ids_str = implode(',', $order_ids);
            
            if ($has_is_admin) {
                $cancel_sql = "SELECT order_id, reason, is_admin, created_at 
                              FROM order_cancellations 
                              WHERE order_id IN ($order_ids_str)";
            } else {
                $cancel_sql = "SELECT order_id, reason, created_at 
                              FROM order_cancellations 
                              WHERE order_id IN ($order_ids_str)";
            }
            
            $cancel_result = $conn->query($cancel_sql);
            
            if ($cancel_result && $cancel_result->num_rows > 0) {
                while ($cancel = $cancel_result->fetch_assoc()) {
                    $order_id = $cancel['order_id'];
                    if (isset($orders[$order_id])) {
                        $orders[$order_id]['cancellation_reason'] = $cancel['reason'];
                        $orders[$order_id]['cancelled_by'] = $has_is_admin && isset($cancel['is_admin']) && $cancel['is_admin'] ? 'Admin' : 'User';
                        $orders[$order_id]['cancelled_at'] = $cancel['created_at'];
                    }
                }
            }
        }
    }

    // Fetch order items for each order
    if (!empty($orders)) {
        $order_ids = array_keys($orders);
        if (!empty($order_ids)) {
            $order_ids_str = implode(',', $order_ids);
            $sql_items = "SELECT 
                oi.order_id,
                oi.quantity,
                oi.price,
                p.item_name
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id IN ($order_ids_str)";
            $items_result = $conn->query($sql_items);
            
            if ($items_result && $items_result->num_rows > 0) {
                while ($item = $items_result->fetch_assoc()) {
                    $orders[$item['order_id']]['items'][] = [
                        'item_name' => $item['item_name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price']
                    ];
                }
            }
        }
    }
}
$stmt->close();
    } catch (Exception $e) {
        // Handle error
        error_log("Error in User-Purchase.php orders list: " . $e->getMessage());
        // Display user-friendly error message
        echo '<div class="alert alert-danger">
                <p>Sorry, we couldn\'t retrieve your order history. Please try again or contact customer support.</p>
              </div>';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title><?= $single_order ? "Order #" . $single_order['id'] : "My Purchases" ?> - Captain's Brew Cafe</title>
  <link rel="icon" href="/public/images/LOGO.png" sizes="any" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <?php if ($single_order && !empty($single_order['lat']) && !empty($single_order['lon'])): ?>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
  <?php endif; ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: #f5f6f5;
      color: #4a3b2b;
      min-height: 100vh;
    }

    .header {
      display: flex;
      align-items: center;
      padding: 1rem 2rem;
      background: linear-gradient(135deg, #FFFAEE, #FFDBB5);
      box-shadow: 0 2px 5px rgba(74, 59, 43, 0.2);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .back-btn {
      display: flex;
      align-items: center;
      width: 150px;
      gap: 0.5rem;
      background: #2C6E8A;
      color: #fff;
      border: none;
      padding: 0.75rem 1.5rem;
      margin: 1vw;
      border-radius: 8px;
      font-size: 1rem;
      cursor: pointer;
      transition: background-color 0.3s, transform 0.2s;
      text-decoration: none;
    }

    .back-btn:hover {
      background: #235A73;
      transform: translateY(-2px);
    }

    .logo-section {
      margin-left: auto;
      margin-right: auto;
    }

    .logo-section img {
      width: 180px;
      transition: transform 0.3s;
    }

    .logo-section img:hover {
      transform: scale(1.05);
    }

    .purchases-container {
      padding: 2rem;
      max-width: 1400px;
      margin: 0 auto;
    }

    .purchases-title {
      font-size: 2rem;
      color: #2C6E8A;
      margin-bottom: 1rem;
      text-align: center;
      font-weight: 600;
    }

    .filter-container {
      display: flex;
      justify-content: center;
      margin-bottom: 1.5rem;
    }

    .filter-select {
      padding: 0.6rem 1rem;
      border: 1px solid #A9D6E5;
      border-radius: 6px;
      background: #fff;
      color: #4a3b2b;
      font-size: 0.95rem;
      cursor: pointer;
      transition: border-color 0.3s, box-shadow 0.3s;
    }

    .filter-select:focus {
      outline: none;
      border-color: #2C6E8A;
      box-shadow: 0 0 5px rgba(44, 110, 138, 0.3);
    }

    .purchases-table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .purchases-table th {
      background: #2C6E8A;
      color: #fff;
      padding: 1rem;
      text-align: left;
      font-weight: 500;
    }

    .purchases-table td {
      padding: 1rem;
      border-bottom: 1px solid #eee;
    }

    .purchases-table tr:last-child td {
      border-bottom: none;
    }

    .status {
      display: inline-block;
      padding: 0.35rem 0.75rem;
      border-radius: 50px;
      font-size: 0.85rem;
      font-weight: 500;
    }

    .status-pending {
      background-color: #FFF3CD;
      color: #856404;
    }

    .status-approved {
      background-color: #D1ECF1;
      color: #0C5460;
    }

    .status-delivered {
      background-color: #D4EDDA;
      color: #155724;
    }

    .status-rejected, .status-cancelled {
      background-color: #F8D7DA;
      color: #721C24;
    }

    .status-out-for-delivery {
      background-color: #CCE5FF;
      color: #004085;
    }

    .view-btn {
      background: #2C6E8A;
      color: #fff;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      cursor: pointer;
      transition: background-color 0.3s;
      text-decoration: none;
      font-size: 0.9rem;
      display: inline-block;
      margin-bottom: 0.5rem;
    }

    .view-btn:hover {
      background: #235A73;
    }

    .cancel-btn {
      background: #DC3545;
      color: #fff;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      cursor: pointer;
      transition: background-color 0.3s;
      font-size: 0.9rem;
    }

    .cancel-btn:hover {
      background: #C82333;
    }

    .no-orders {
      text-align: center;
      padding: 2rem;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    /* Order details styles */
    .order-details-container {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1.5rem;
      max-width: 1200px;
      margin: 0 auto;
    }

    @media (min-width: 992px) {
      .order-details-container {
        grid-template-columns: 3fr 2fr;
      }
    }

    .order-info-card, .delivery-info-card, .order-items-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .card-title {
      color: #2C6E8A;
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid #eee;
    }

    .info-row {
      display: flex;
      margin-bottom: 0.75rem;
    }

    .info-label {
      font-weight: 500;
      width: 150px;
      color: #4a3b2b;
    }

    .info-value {
      flex: 1;
    }

    .order-item {
      display: flex;
      align-items: center;
      padding: 0.75rem 0;
      border-bottom: 1px solid #f0f0f0;
    }

    .order-item:last-child {
      border-bottom: none;
    }

    .item-image {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
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

    .item-price {
      font-weight: 500;
      color: #2C6E8A;
    }

    .order-summary {
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid #eee;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.5rem;
    }

    .summary-row.total {
      font-weight: 600;
      font-size: 1.1rem;
      color: #2C6E8A;
      padding-top: 0.5rem;
      margin-top: 0.5rem;
      border-top: 1px solid #eee;
    }

    #map-container {
      height: 300px;
      border-radius: 8px;
      margin-top: 1rem;
      overflow: hidden;
    }
    
    /* Responsive styles for mobile devices */
    @media (max-width: 768px) {
      .header {
        padding: 1rem;
      }

      .back-btn {
        padding: 0.5rem 1rem;
        width: auto;
        font-size: 0.9rem;
        margin: 0.5vw;
      }

      .logo-section img {
        width: 140px;
      }

      .purchases-container {
        padding: 1rem;
      }

      .purchases-title {
        font-size: 1.5rem;
        margin-bottom: 0.75rem;
      }

      /* Responsive table for mobile */
      .purchases-table {
        display: block;
        width: 100%;
      }

      .purchases-table thead {
        display: none; /* Hide table header on mobile */
      }
      
      .purchases-table tbody {
        display: block;
        width: 100%;
      }

      .purchases-table tr {
        display: block;
        margin-bottom: 1rem;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      }

      .purchases-table td {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        text-align: right;
        border-bottom: 1px solid #f0f0f0;
      }

      .purchases-table td::before {
        content: attr(data-label);
        font-weight: 500;
        text-align: left;
        color: #2C6E8A;
      }
      
      .info-row {
        flex-direction: column;
        margin-bottom: 1rem;
      }
      
      .info-label {
        width: 100%;
        margin-bottom: 0.25rem;
      }
      
      .order-item {
        flex-wrap: wrap;
      }
      
      .item-image {
        margin-bottom: 0.5rem;
      }
      
      .item-price {
        width: 100%;
        text-align: right;
        margin-top: 0.5rem;
      }
    }

    /* Cancellation info styles */
    .cancellation-info {
      margin-top: 1rem;
      padding: 1rem;
      background-color: #f8f9fa;
      border-left: 4px solid #DC3545;
      border-radius: 4px;
    }
    
    .cancellation-info h3 {
      color: #DC3545;
      font-size: 1.1rem;
      margin-bottom: 0.5rem;
    }
    
    .cancellation-info p {
      margin: 0.25rem 0;
      font-size: 0.95rem;
    }
    
    .cancellation-reason {
      font-weight: 500;
      margin-top: 0.5rem;
    }
    
    .info-badge {
      display: inline-flex;
      align-items: center;
      padding: 0.25rem 0.5rem;
      background: #2C6E8A;
      color: white;
      border-radius: 4px;
      font-size: 0.8rem;
      margin-left: 0.5rem;
      cursor: pointer;
    }
    
    .info-badge i {
      margin-right: 0.25rem;
    }
  </style>
</head>

<body>
  <header class="header">
    <a href="/views/users/User-Home.php" class="back-btn">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z" />
      </svg>
      Back
    </a>
    <div class="logo-section">
      <img src="/public/images/LOGO.png" alt="Captain's Brew Logo">
    </div>    
  </header>

  <div class="purchases-container">
    <?php if ($single_order): ?>
      <!-- Single Order View -->
      <h1 class="purchases-title">Order #<?= $single_order['id'] ?> Details</h1>
      
      <div class="order-details-container">
        <div class="left-column">
          <div class="order-info-card">
            <h2 class="card-title">Order Information</h2>
            <div class="info-row">
              <div class="info-label">Order ID:</div>
              <div class="info-value">#<?= $single_order['id'] ?></div>
            </div>
            <div class="info-row">
              <div class="info-label">Date:</div>
              <div class="info-value"><?= date('F j, Y', strtotime($single_order['order_date'])) ?></div>
            </div>
            <div class="info-row">
              <div class="info-label">Time:</div>
              <div class="info-value"><?= date('g:i A', strtotime($single_order['order_time'])) ?></div>
            </div>
            <div class="info-row">
              <div class="info-label">Status:</div>
              <div class="info-value">
                <span class="status status-<?= strtolower(str_replace(' ', '-', $single_order['status'])) ?>">
                  <?= $single_order['status'] ?>
                </span>
              </div>
            </div>
            <div class="info-row">
              <div class="info-label">Payment Method:</div>
              <div class="info-value"><?= $single_order['payment_method'] ?></div>
            </div>
            
            <?php if (($single_order['status'] === 'Cancelled' || $single_order['status'] === 'Rejected') && !empty($single_order['cancellation_reason'])): ?>
            <div class="cancellation-info">
              <h3>
                <?= $single_order['status'] === 'Cancelled' ? 'Order Cancelled' : 'Order Rejected' ?>
              </h3>
              <p>This order was <?= strtolower($single_order['status']) ?> on <?= date('F j, Y g:i A', strtotime($single_order['updated_at'])) ?></p>
              <p class="cancellation-reason">Reason: <?= htmlspecialchars($single_order['cancellation_reason']) ?></p>
            </div>
            <?php endif; ?>
          </div>
          
          <div class="order-items-card">
            <h2 class="card-title">Order Items</h2>
            <?php foreach ($single_order['items'] as $item): ?>
              <div class="order-item">
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
                    <span>Qty: <?= $item['quantity'] ?></span>
                  </div>
                </div>
                <div class="item-price">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
              </div>
            <?php endforeach; ?>
            
            <div class="order-summary">
              <div class="summary-row">
                <span>Subtotal:</span>
                <span>₱<?= number_format($single_order['total_amount'] - ($single_order['delivery_fee'] ?? 30.00), 2) ?></span>
              </div>
              <div class="summary-row">
                <span>Delivery Fee:</span>
                <span>₱<?= number_format($single_order['delivery_fee'] ?? 30.00, 2) ?></span>
              </div>
              <div class="summary-row total">
                <span>Total:</span>
                <span>₱<?= number_format($single_order['total_amount'], 2) ?></span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="right-column">
          <div class="delivery-info-card">
            <h2 class="card-title">Delivery Information</h2>
            <div class="info-row">
              <div class="info-label">Address:</div>
              <div class="info-value"><?= htmlspecialchars($single_order['delivery_address']) ?></div>
            </div>
            <?php if ($single_order['rider_name']): ?>
              <div class="info-row">
                <div class="info-label">Rider:</div>
                <div class="info-value"><?= htmlspecialchars($single_order['rider_name']) ?></div>
              </div>
              <?php if ($single_order['rider_contact']): ?>
                <div class="info-row">
                  <div class="info-label">Rider Contact:</div>
                  <div class="info-value"><?= htmlspecialchars($single_order['rider_contact']) ?></div>
                </div>
              <?php endif; ?>
            <?php else: ?>
              <div class="info-row">
                <div class="info-label">Rider:</div>
                <div class="info-value">Not yet assigned</div>
              </div>
            <?php endif; ?>
            
            <?php if (!empty($single_order['lat']) && !empty($single_order['lon'])): ?>
              <div id="map-container"></div>
            <?php endif; ?>
          </div>
          
          <div class="actions" style="margin-top: 1rem; text-align: center;">
            <a href="/views/users/User-Purchase.php" class="view-btn">Back to Orders</a>
            
            <?php if ($single_order['status'] === 'Pending'): ?>
              <button onclick="cancelOrder(<?= $single_order['id'] ?>)" class="cancel-btn">Cancel Order</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
    <?php else: ?>
      <!-- Orders List View -->
    <h1 class="purchases-title">My Purchases</h1>
      
    <div class="filter-container">
        <select id="status-filter" class="filter-select" onchange="window.location.href='?status=' + this.value">
          <option value="All" <?= $filter_status === 'All' ? 'selected' : '' ?>>All Orders</option>
          <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
          <option value="Approved" <?= $filter_status === 'Approved' ? 'selected' : '' ?>>Approved</option>
          <option value="Out for Delivery" <?= $filter_status === 'Out for Delivery' ? 'selected' : '' ?>>Out for Delivery</option>
          <option value="Delivered" <?= $filter_status === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
          <option value="Cancelled" <?= $filter_status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
          <option value="Rejected" <?= $filter_status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
      </div>

      <?php if (empty($orders)): ?>
        <div class="no-orders">
          <p>No orders found.</p>
    </div>
      <?php else: ?>
    <table class="purchases-table">
      <thead>
        <tr>
              <th>Order ID</th>
              <th>Date</th>
          <th>Items</th>
              <th>Total</th>
          <th>Status</th>
              <th>Actions</th>
        </tr>
      </thead>
      <tbody>
          <?php foreach ($orders as $order): ?>
            <tr>
                <td data-label="Order ID">#<?= $order['id'] ?></td>
                <td data-label="Date"><?= date('M j, Y g:i A', strtotime($order['date'] . ' ' . $order['time'])) ?></td>
              <td data-label="Items">
                  <?php 
                  $item_count = count($order['items']);
                  if ($item_count > 0) {
                    echo htmlspecialchars($order['items'][0]['item_name']);
                    if ($item_count > 1) {
                      echo ' and ' . ($item_count - 1) . ' more item' . ($item_count > 2 ? 's' : '');
                    }
                  } else {
                    echo 'No items';
                  }
                  ?>
                </td>
                <td data-label="Total">₱<?= number_format($order['total_amount'], 2) ?></td>
                <td data-label="Status">
                  <span class="status status-<?= strtolower(str_replace(' ', '-', $order['status'])) ?>">
                    <?= $order['status'] ?>
                  </span>
                  <?php if (($order['status'] === 'Cancelled' || $order['status'] === 'Rejected') && !empty($order['cancellation_reason'])): ?>
                    <span class="info-badge" onclick="showCancellationInfo('<?= htmlspecialchars($order['cancellation_reason']) ?>', '<?= $order['status'] ?>', '<?= isset($order['cancelled_at']) ? $order['cancelled_at'] : $order['date'] ?>')">
                      <i class="fas fa-info-circle"></i> Info
                    </span>
                  <?php endif; ?>
              </td>
                <td data-label="Actions">
                  <a href="?order_id=<?= $order['id'] ?>" class="view-btn">View Details</a>
                  <?php if ($order['status'] === 'Pending'): ?>
                    <button onclick="cancelOrder(<?= $order['id'] ?>)" class="cancel-btn">Cancel</button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
      </tbody>
    </table>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php if ($single_order && !empty($single_order['lat']) && !empty($single_order['lon'])): ?>
  <script src="/public/js/location-service.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize map with order location
      const orderLocation = {
        lat: <?= $single_order['lat'] ?>,
        lon: <?= $single_order['lon'] ?>
      };
      
      initDeliveryMap('map-container', orderLocation);
    });
  </script>
  <?php else: ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const mapContainer = document.getElementById('map-container');
      if (mapContainer) {
        mapContainer.innerHTML = '<div style="padding: 2rem; text-align: center; color: #666;">No location data available for this order.</div>';
      }
    });
  </script>
  <?php endif; ?>
  
  <script>
    function cancelOrder(orderId) {
      Swal.fire({
        title: 'Cancel Order',
        html: `
          <div style="text-align: left;">
            <p>Please provide a reason for cancelling your order:</p>
            <select id="cancel-reason" class="swal2-select" style="width: 100%; margin-bottom: 10px;">
              <option value="">-- Select a reason --</option>
              <option value="Changed my mind">Changed my mind</option>
              <option value="Ordered by mistake">Ordered by mistake</option>
              <option value="Delivery time too long">Delivery time too long</option>
              <option value="Found better alternative">Found better alternative</option>
              <option value="Other">Other (specify below)</option>
            </select>
            <textarea id="custom-reason" class="swal2-textarea" placeholder="Additional details or custom reason..." style="display: none; width: 100%;"></textarea>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Cancel Order',
        cancelButtonText: 'Keep Order',
        didOpen: () => {
          const selectEl = document.getElementById('cancel-reason');
          const textareaEl = document.getElementById('custom-reason');
          
          selectEl.addEventListener('change', function() {
            if (this.value === 'Other') {
              textareaEl.style.display = 'block';
            } else {
              textareaEl.style.display = 'none';
            }
          });
        },
        preConfirm: () => {
          const reason = document.getElementById('cancel-reason').value;
          const customReason = document.getElementById('custom-reason').value;
          
          if (!reason) {
            Swal.showValidationMessage('Please select a reason');
            return false;
          }
          
          if (reason === 'Other' && !customReason) {
            Swal.showValidationMessage('Please provide details for "Other" reason');
            return false;
          }
          
          return { reason, customReason };
        }
      }).then((result) => {
        if (result.isConfirmed) {
          const { reason, customReason } = result.value;
          const finalReason = reason === 'Other' ? customReason : reason;
          
          // Send cancel request
          fetch('/controllers/handle-cancel-order.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              order_id: orderId,
              reason: finalReason
            }),
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: 'Order Canceled',
                text: 'Your order has been canceled successfully.',
                timer: 2000,
                showConfirmButton: false
              }).then(() => {
                window.location.reload();
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to cancel order'
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
    
    function showCancellationInfo(reason, status, date) {
      const formattedDate = new Date(date).toLocaleString();
      const title = status === 'Cancelled' ? 'Order Cancelled' : 'Order Rejected';
      
      Swal.fire({
        title: title,
        html: `
          <div style="text-align: left; padding: 10px;">
            <p><strong>Date:</strong> ${formattedDate}</p>
            <p><strong>Reason:</strong> ${reason}</p>
          </div>
        `,
        icon: status === 'Cancelled' ? 'info' : 'warning',
        confirmButtonColor: '#2C6E8A'
      });
    }
  </script>
</body>
</html>