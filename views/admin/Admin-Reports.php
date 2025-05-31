<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
requireAdmin();

// Ensure session is started
if (!isset($_SESSION)) {
    session_start();
}

// Verify admin is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['loggedin'])) {
    header('Location: /views/auth/login.php');
    exit();
}

// Initialize arrays and fetch orders with joined user and product details
$allOrders = [];
try {
    $sql = "SELECT o.id AS order_id, o.user_id, u.username, u.email, u.contact,
                   o.status, o.created_at, o.updated_at, o.cancellation_reason,
                   o.total_amount, o.payment_method, o.delivery_address, o.rider_id, o.customer_contact,
                   r.name as rider_name, r.contact as rider_contact,
                   oi.product_id, p.item_name, p.item_image, oi.quantity, oi.price, oi.variation
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN riders r ON o.rider_id = r.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.status IN ('Rejected', 'Pending', 'Approved', 'Out for Delivery', 'Delivered', 'Cancelled')
            ORDER BY o.created_at DESC";
    
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Group orders by order_id and user
        while ($row = $result->fetch_assoc()) {
            $order_id = $row['order_id'];
            if (!isset($allOrders[$order_id])) {
                $allOrders[$order_id] = [
                    'order_id' => $order_id,
                    'user_id' => $row['user_id'],
                    'username' => $row['username'] ?? 'Guest',
                    'email' => $row['email'] ?? 'N/A',
                    'contact' => $row['customer_contact'] ? $row['customer_contact'] : ($row['contact'] ?? 'N/A'),
                    'contact_number' => $row['customer_contact'],
                    'status' => $row['status'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'cancellation_reason' => $row['cancellation_reason'],
                    'total_amount' => $row['total_amount'] ?? 0,
                    'payment_method' => $row['payment_method'] ?? 'N/A',
                    'delivery_address' => $row['delivery_address'] ?? 'N/A',
                    'rider_id' => $row['rider_id'],
                    'rider_name' => $row['rider_name'] ?? 'Not Assigned',
                    'rider_contact' => $row['rider_contact'] ?? 'N/A',
                    'items' => []
                ];
            }
            if ($row['product_id']) {
                $allOrders[$order_id]['items'][] = [
                    'item_name' => $row['item_name'] ?? 'Unknown Item',
                    'item_image' => $row['item_image'] ?? '/public/images/default-item.jpg',
                    'quantity' => $row['quantity'],
                    'price' => $row['price'],
                    'variation' => $row['variation'] ?? ''
                ];
            }
        }
    }
    
    // Check if order_cancellations table exists and fetch detailed cancellation info
    $table_check = $conn->query("SHOW TABLES LIKE 'order_cancellations'");
    if ($table_check && $table_check->num_rows > 0 && !empty($allOrders)) {
        // Check if is_admin column exists
        $column_check = $conn->query("SHOW COLUMNS FROM order_cancellations LIKE 'is_admin'");
        $has_is_admin = $column_check && $column_check->num_rows > 0;
        
        $order_ids = implode(',', array_keys($allOrders));
        
        if ($has_is_admin) {
            $cancel_sql = "SELECT order_id, reason, is_admin, created_at 
                          FROM order_cancellations 
                          WHERE order_id IN ($order_ids)";
        } else {
            $cancel_sql = "SELECT order_id, reason, created_at 
                          FROM order_cancellations 
                          WHERE order_id IN ($order_ids)";
        }
        
        $cancel_result = $conn->query($cancel_sql);
        
        if ($cancel_result && $cancel_result->num_rows > 0) {
            while ($cancel = $cancel_result->fetch_assoc()) {
                $order_id = $cancel['order_id'];
                if (isset($allOrders[$order_id])) {
                    $allOrders[$order_id]['cancellation_reason'] = $cancel['reason'];
                    $allOrders[$order_id]['cancelled_by'] = $has_is_admin && isset($cancel['is_admin']) && $cancel['is_admin'] ? 'Admin' : 'User';
                    $allOrders[$order_id]['cancelled_at'] = $cancel['created_at'];
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage(), 3, __DIR__ . '/error.log');
    $error_message = "Failed to load orders. Please try again later.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>Admin Reports - Captain's Brew Cafe</title>
  <link rel="icon" href="/public/images/LOGO.png" sizes="any" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      background: #f8f9fa;
      color: #4a3b2b;
    }

    /* Header */
    .header {
      display: flex;
      align-items: center;
      padding: 1rem 2rem;
      background: linear-gradient(135deg, #FFFAEE, #FFDBB5);
      box-shadow: 0 2px 5px rgba(74, 59, 43, 0.3);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .logo-section img {
      width: 200px;
      margin: 0px 100px 0px 100px;
      transition: transform 0.3s;
    }

    .logo-section img:hover {
      transform: scale(1.1);
    }

    .button-container {
      display: flex;
      gap: 3rem;
    }

    .nav-button {
      background: none;
      border: none;
      color: #4a3b2b;
      font-size: 1rem;
      padding: 1rem 2rem;
      cursor: pointer;
      border-radius: 10px;
      transition: all 0.3s;
      text-decoration: none;
    }

    .nav-button:hover, .nav-button.active {
      background-color: #2C6E8A;
      color: #fff;
    }

    /* Dashboard Summary */
    .dashboard-summary {
      display: flex;
      justify-content: space-between;
      padding: 1.5rem 2rem;
      margin-bottom: 1rem;
      background: linear-gradient(135deg, #2C6E8A, #235A73);
      color: white;
      border-radius: 10px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .summary-card {
      background: rgba(255, 255, 255, 0.2);
      padding: 1.5rem;
      border-radius: 8px;
      text-align: center;
      min-width: 180px;
      backdrop-filter: blur(5px);
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .summary-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    }

    .summary-icon {
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }

    .summary-value {
      font-size: 1.75rem;
      font-weight: 600;
      margin-bottom: 0.25rem;
    }

    .summary-label {
      font-size: 0.9rem;
      opacity: 0.8;
    }

    /* Reports Container */
    .reports-container {
      padding: 2rem;
      display: flex;
      gap: 1.5rem;
    }

    .report-filter {
      background: white;
      padding: 1.5rem;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      min-width: 250px;
      height: fit-content;
    }

    .filter-title {
      color: #2C6E8A;
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid #A9D6E5;
    }

    .filter-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 1rem;
      margin-bottom: 0.5rem;
      cursor: pointer;
      color: #4a3b2b;
      font-size: 1rem;
      border-radius: 6px;
      transition: all 0.2s;
    }

    .filter-item:hover {
      background-color: #f0f7fa;
    }

    .filter-item.active {
      background-color: #2C6E8A;
      color: #fff;
      font-weight: 500;
    }

    .filter-icon {
      font-size: 1.1rem;
    }

    .report-table {
      background: white;
      border-radius: 10px;
      padding: 1.5rem;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      width: 100%;
    }

    .report-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .report-title {
      font-size: 1.5rem;
      color: #2C6E8A;
      font-weight: 600;
    }

    .export-btn {
      background: #2C6E8A;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: background 0.3s;
    }

    .export-btn:hover {
      background: #235A73;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }

    th, td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid rgba(74, 59, 43, 0.1);
    }

    th {
      background: #f0f7fa;
      color: #2C6E8A;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.9rem;
    }

    tbody tr {
      transition: background-color 0.2s;
    }

    tbody tr:hover {
      background-color: #f9f9f9;
    }

    td {
      color: #4a3b2b;
      font-size: 0.95rem;
    }

    .item-img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
      vertical-align: middle;
      margin-right: 0.5rem;
    }

    .items-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .item-row {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      background: #f8f9fa;
      padding: 0.5rem;
      border-radius: 6px;
    }

    .status-badge {
      display: inline-block;
      padding: 0.35rem 0.75rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 500;
      text-align: center;
      min-width: 100px;
    }

    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .status-approved {
      background-color: #d4edda;
      color: #155724;
    }

    .status-delivery {
      background-color: #cce5ff;
      color: #004085;
    }

    .status-delivered {
      background-color: #d1e7dd;
      color: #0f5132;
    }

    .status-rejected, .status-cancelled {
      background-color: #f8d7da;
      color: #721c24;
    }

    .no-reports-message, .error-message {
      text-align: center;
      padding: 3rem 2rem;
      color: #4a3b2b;
      font-size: 1.2rem;
      font-style: italic;
      background: #f8f9fa;
      border-radius: 8px;
      border: 1px dashed #d1d5db;
    }

    .view-reason-btn {
      background: #2C6E8A;
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .view-reason-btn:hover {
      background: #235A73;
      transform: translateY(-2px);
    }
    
    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(5px);
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .modal-content {
      background: white;
      margin: 5% auto;
      padding: 2rem;
      border-radius: 12px;
      width: 90%;
      max-width: 600px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      position: relative;
      color: #4a3b2b;
      text-align: left;
      animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
      from { transform: translateY(-50px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid #e5e7eb;
    }

    .modal-header h2 {
      color: #2C6E8A;
      font-size: 1.5rem;
      font-weight: 600;
    }

    .close-btn {
      color: #2C6E8A;
      font-size: 1.5rem;
      cursor: pointer;
      transition: all 0.3s;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
    }

    .close-btn:hover {
      background-color: #f0f7fa;
      color: #235A73;
    }
    
    .cancellation-details {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 1.5rem;
    }
    
    .cancellation-details p {
      margin: 0.75rem 0;
      display: flex;
      align-items: flex-start;
    }
    
    .reason-label {
      font-weight: 600;
      color: #2C6E8A;
      min-width: 150px;
      display: inline-block;
    }

    .reason-value {
      flex: 1;
    }

    .reason-highlight {
      background-color: #fff3cd;
      padding: 1rem;
      border-radius: 6px;
      border-left: 4px solid #2C6E8A;
      margin-top: 1rem;
    }
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/partials/header.php'; ?>

  <?php
    // Calculate summary statistics
    $totalOrders = count($allOrders);
    $pendingOrders = 0;
    $deliveredOrders = 0;
    $cancelledOrders = 0;
    $totalRevenue = 0;
    
    foreach ($allOrders as $order) {
      if ($order['status'] === 'Pending') $pendingOrders++;
      if ($order['status'] === 'Delivered') {
        $deliveredOrders++;
        $totalRevenue += $order['total_amount'];
      }
      if ($order['status'] === 'Cancelled' || $order['status'] === 'Rejected') $cancelledOrders++;
    }
  ?>

  <div class="reports-container">
    <div class="report-filter">
      <h3 class="filter-title">Filter Orders</h3>
      <div class="filter-item active" data-status="all">
        <i class="fas fa-list-ul filter-icon"></i>
        All Orders
      </div>
      <div class="filter-item" data-status="Pending">
        <i class="fas fa-clock filter-icon"></i>
        Pending Orders
      </div>
      <div class="filter-item" data-status="Approved">
        <i class="fas fa-check-circle filter-icon"></i>
        Approved Orders
      </div>
      <div class="filter-item" data-status="Out for Delivery">
        <i class="fas fa-motorcycle filter-icon"></i>
        Out for Delivery
      </div>
      <div class="filter-item" data-status="Delivered">
        <i class="fas fa-check-double filter-icon"></i>
        Delivered Orders
      </div>
      <div class="filter-item" data-status="Rejected">
        <i class="fas fa-times-circle filter-icon"></i>
        Rejected Orders
      </div>
      <div class="filter-item" data-status="Cancelled">
        <i class="fas fa-ban filter-icon"></i>
        Cancelled Orders
      </div>
    </div>

    <div class="report-table">
      <div class="dashboard-summary">
        <div class="summary-card">
          <i class="fas fa-shopping-cart summary-icon"></i>
          <div class="summary-value"><?php echo $totalOrders; ?></div>
          <div class="summary-label">Total Orders</div>
        </div>
        <div class="summary-card">
          <i class="fas fa-clock summary-icon"></i>
          <div class="summary-value"><?php echo $pendingOrders; ?></div>
          <div class="summary-label">Pending Orders</div>
        </div>
        <div class="summary-card">
          <i class="fas fa-check-circle summary-icon"></i>
          <div class="summary-value"><?php echo $deliveredOrders; ?></div>
          <div class="summary-label">Completed Orders</div>
        </div>
        <div class="summary-card">
          <i class="fas fa-ban summary-icon"></i>
          <div class="summary-value"><?php echo $cancelledOrders; ?></div>
          <div class="summary-label">Cancelled Orders</div>
        </div>
        <div class="summary-card">
          <i class="fas fa-money-bill-wave summary-icon"></i>
          <div class="summary-value">₱<?php echo number_format($totalRevenue, 2); ?></div>
          <div class="summary-label">Total Revenue</div>
        </div>
      </div>
      
      <div class="report-header">
      <h2 class="report-title">Order Reports</h2>
        <button class="export-btn" onclick="exportToCSV()">
          <i class="fas fa-download"></i> Export Report
        </button>
      </div>
      
      <div id="orders-table">
        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php elseif (empty($allOrders)): ?>
            <p class="no-reports-message">No orders available for reporting.</p>
        <?php else: ?>
            <table>
              <thead>
                <tr>
                  <th>Order Number</th>
                  <th>User</th>
                  <th>Contact</th>
                  <th>Ordered Items</th>
                  <th>Order Date</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($allOrders as $order): ?>
                  <tr class="order-row" data-status="<?php echo htmlspecialchars($order['status']); ?>">
                    <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                    <td>
                      <strong><?php echo htmlspecialchars($order['username']); ?></strong><br>
                      <small><?php echo htmlspecialchars($order['email']); ?></small>
                    </td>
                    <td>
                      <?php echo htmlspecialchars($order['contact']); ?>
                    </td>
                    <td>
                      <div class="items-list">
                        <?php foreach ($order['items'] as $item): ?>
                          <div class="item-row">
                            <img src="/public/<?php echo htmlspecialchars($item['item_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['item_name']); ?>" 
                                 class="item-img">
                            <span>
                              <strong><?php echo htmlspecialchars($item['item_name']); ?></strong> x 
                              <?php echo htmlspecialchars($item['quantity']); ?>
                              <br>
                              <small>₱<?php echo number_format($item['price'], 2); ?></small>
                            </span>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </td>
                    <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                    <td>
                      <?php 
                        $statusClass = '';
                        switch($order['status']) {
                          case 'Pending':
                            $statusClass = 'status-pending';
                            break;
                          case 'Approved':
                            $statusClass = 'status-approved';
                            break;
                          case 'Out for Delivery':
                            $statusClass = 'status-delivery';
                            break;
                          case 'Delivered':
                            $statusClass = 'status-delivered';
                            break;
                          case 'Rejected':
                          case 'Cancelled':
                            $statusClass = 'status-cancelled';
                            break;
                        }
                      ?>
                      <span class="status-badge <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($order['status']); ?>
                      </span>
                    </td>
                    <td>
                      <?php if (($order['status'] === 'Rejected' || $order['status'] === 'Cancelled') && !empty($order['cancellation_reason'])): ?>
                        <button class="view-reason-btn" onclick='showCancellationDetails(<?= json_encode($order) ?>)'>
                          <i class="fas fa-info-circle"></i> View Reason
                        </button>
                      <?php elseif ($order['status'] === 'Delivered'): ?>
                        <button class="view-reason-btn" onclick='showDeliveryDetails(<?= json_encode($order) ?>)'>
                          <i class="fas fa-info-circle"></i> View Details
                        </button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div id="cancellationModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2><i class="fas fa-ban"></i> Cancellation Details</h2>
        <span class="close-btn" onclick="closeCancellationModal()">×</span>
      </div>
      <div class="cancellation-details">
        <p>
          <span class="reason-label">Order ID:</span>
          <span class="reason-value" id="cancel-order-id"></span>
        </p>
        <p>
          <span class="reason-label">Status:</span>
          <span class="reason-value" id="cancel-status"></span>
        </p>
        <p>
          <span class="reason-label">Cancelled By:</span>
          <span class="reason-value" id="cancel-by"></span>
        </p>
        <p>
          <span class="reason-label">Date:</span>
          <span class="reason-value" id="cancel-date"></span>
        </p>
        <div class="reason-highlight">
          <p>
            <span class="reason-label">Reason:</span>
            <span class="reason-value" id="cancel-reason"></span>
          </p>
        </div>
      </div>
    </div>
  </div>
  
  <div id="deliveryModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2><i class="fas fa-check-circle"></i> Delivery Details</h2>
        <span class="close-btn" onclick="closeDeliveryModal()">×</span>
      </div>
      <div class="cancellation-details">
        <p>
          <span class="reason-label">Order ID:</span>
          <span class="reason-value" id="delivery-order-id"></span>
        </p>
        <p>
          <span class="reason-label">Status:</span>
          <span class="reason-value" id="delivery-status"></span>
        </p>
        <p>
          <span class="reason-label">Completed On:</span>
          <span class="reason-value" id="delivery-date"></span>
        </p>
        <p>
          <span class="reason-label">Total Amount:</span>
          <span class="reason-value" id="delivery-amount"></span>
        </p>
        <p>
          <span class="reason-label">Payment Method:</span>
          <span class="reason-value" id="delivery-payment"></span>
        </p>
        <p>
          <span class="reason-label">Customer:</span>
          <span class="reason-value" id="delivery-customer"></span>
        </p>
        <p>
          <span class="reason-label">Contact:</span>
          <span class="reason-value" id="delivery-contact"></span>
        </p>
        <p>
          <span class="reason-label">Delivery Address:</span>
          <span class="reason-value" id="delivery-address"></span>
        </p>
        <p>
          <span class="reason-label">Delivered By:</span>
          <span class="reason-value" id="delivery-rider"></span>
        </p>
        <p>
          <span class="reason-label">Rider Contact:</span>
          <span class="reason-value" id="delivery-rider-contact"></span>
        </p>
        <div class="reason-highlight">
          <p>
            <span class="reason-label">Items:</span>
            <span class="reason-value" id="delivery-items"></span>
          </p>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const filterItems = document.querySelectorAll('.filter-item');
      const orderRows = document.querySelectorAll('.order-row');

      filterItems.forEach(item => {
        item.addEventListener('click', function() {
          // Remove active class from all filters
          filterItems.forEach(fi => fi.classList.remove('active'));
          // Add active class to clicked filter
          this.classList.add('active');

          const status = this.getAttribute('data-status');
          
          // Show/hide rows based on filter
          orderRows.forEach(row => {
            if (status === 'all' || row.getAttribute('data-status') === status) {
              row.style.display = '';
            } else {
              row.style.display = 'none';
            }
          });
        });
      });
    });
    
    function showCancellationDetails(order) {
      document.getElementById('cancel-order-id').textContent = order.order_id;
      document.getElementById('cancel-status').textContent = order.status;
      document.getElementById('cancel-by').textContent = order.cancelled_by || 'Unknown';
      
      const cancelDate = order.cancelled_at ? new Date(order.cancelled_at) : new Date(order.created_at);
      document.getElementById('cancel-date').textContent = cancelDate.toLocaleString();
      
      document.getElementById('cancel-reason').textContent = order.cancellation_reason || 'No reason provided';
      
      document.getElementById('cancellationModal').style.display = 'block';
    }
    
    function closeCancellationModal() {
      document.getElementById('cancellationModal').style.display = 'none';
    }
    
    function showDeliveryDetails(order) {
      document.getElementById('delivery-order-id').textContent = order.order_id;
      document.getElementById('delivery-status').textContent = order.status;
      document.getElementById('delivery-date').textContent = new Date(order.updated_at || order.created_at).toLocaleString();
      document.getElementById('delivery-amount').textContent = '₱' + parseFloat(order.total_amount).toFixed(2);
      document.getElementById('delivery-payment').textContent = order.payment_method;
      document.getElementById('delivery-customer').textContent = order.username;
      document.getElementById('delivery-contact').textContent = order.contact;
      document.getElementById('delivery-address').textContent = order.delivery_address;
      document.getElementById('delivery-rider').textContent = order.rider_name || 'Not Assigned';
      document.getElementById('delivery-rider-contact').textContent = order.rider_contact || 'N/A';
      
      // Format items list
      let itemsList = '';
      if (order.items && order.items.length > 0) {
        itemsList = order.items.map(item => {
          const variation = item.variation ? ` (${item.variation})` : '';
          return `${item.item_name}${variation} x ${item.quantity} (₱${parseFloat(item.price).toFixed(2)})`;
        }).join(', ');
      } else {
        itemsList = 'No items available';
      }
      document.getElementById('delivery-items').textContent = itemsList;
      
      document.getElementById('deliveryModal').style.display = 'block';
    }
    
    function closeDeliveryModal() {
      document.getElementById('deliveryModal').style.display = 'none';
    }
    
    window.onclick = function(event) {
      const cancellationModal = document.getElementById('cancellationModal');
      const deliveryModal = document.getElementById('deliveryModal');
      
      if (event.target === cancellationModal) {
        closeCancellationModal();
      }
      
      if (event.target === deliveryModal) {
        closeDeliveryModal();
      }
    };
    
    function exportToCSV() {
      // Get all visible rows
      const rows = Array.from(document.querySelectorAll('.order-row')).filter(row => row.style.display !== 'none');
      
      if (rows.length === 0) {
        alert('No data to export');
        return;
      }
      
      // Get order data from PHP
      const orderData = <?php echo json_encode(array_values($allOrders)); ?>;
      
      // Create CSV content
      let csvContent = 'Order ID,Customer,Email,Contact,Status,Order Date,Total Amount,Payment Method,Delivery Address\n';
      
      rows.forEach(row => {
        const orderId = row.cells[0].textContent.trim().replace('#', '');
        const username = row.cells[1].querySelector('strong').textContent.trim();
        const email = row.cells[1].querySelector('small').textContent.trim();
        const contact = row.cells[2].textContent.trim();
        const status = row.cells[4].textContent.trim();
        const orderDate = row.cells[3].textContent.trim();
        
        // Find the matching order in the orderData array
        const orderInfo = orderData.find(order => order.order_id == orderId);
        const totalAmount = orderInfo ? '₱' + parseFloat(orderInfo.total_amount).toFixed(2) : '₱0.00';
        const paymentMethod = orderInfo ? orderInfo.payment_method : 'N/A';
        const deliveryAddress = orderInfo ? orderInfo.delivery_address : 'N/A';
        
        // Escape fields that might contain commas
        const escapedUsername = username.includes(',') ? `"${username}"` : username;
        const escapedEmail = email.includes(',') ? `"${email}"` : email;
        const escapedContact = contact.includes(',') ? `"${contact}"` : contact;
        const escapedAddress = deliveryAddress.includes(',') ? `"${deliveryAddress}"` : deliveryAddress;
        
        csvContent += `${orderId},${escapedUsername},${escapedEmail},${escapedContact},${status},${orderDate},${totalAmount},${paymentMethod},${escapedAddress}\n`;
      });
      
      // Create download link
      const encodedUri = encodeURI('data:text/csv;charset=utf-8,' + csvContent);
      const link = document.createElement('a');
      link.setAttribute('href', encodedUri);
      link.setAttribute('download', `orders_report_${new Date().toISOString().slice(0,10)}.csv`);
      document.body.appendChild(link);
      
      // Trigger download
      link.click();
      document.body.removeChild(link);
    }
  </script>
  
<script src="/public/js/auth.js"></script>
</body>
</html>