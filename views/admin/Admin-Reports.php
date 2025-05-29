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
                   o.total_amount, o.payment_method, o.delivery_address, o.rider_id,
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
                    'contact' => $row['contact'] ?? 'N/A',
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
      background: #fff;
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

    /* Reports Container */
    .reports-container {
      padding: 2rem;
      display: flex;
    }

    .report-filter {
      background: #D7B9A9;
      padding: 1rem;
      border-radius: 10px;
      margin-right: 2rem;
      min-width: 200px;
    }

    .filter-item {
      padding: 0.5rem;
      cursor: pointer;
      color: #4a3b2b;
      font-size: 1rem;
    }

    .filter-item:hover, .filter-item.active {
      background-color: #2C6E8A;
      color: #fff;
    }

    .report-table {
      background: #A9D6E5;
      border-radius: 10px;
      padding: 1rem;
      box-shadow: 0 5px 15px rgba(74, 59, 43, 0.2);
      width: 100%;
    }

    .report-title {
      font-size: 1.5rem;
      color: #2C6E8A;
      margin-bottom: 1rem;
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
      background: #87BFD1;
      color: #2C6E8A;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.9rem;
    }

    td {
      color: #4a3b2b;
      font-size: 0.9rem;
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
      gap: 0.5rem;
    }

    .item-row {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .no-reports-message, .error-message {
      text-align: center;
      padding: 2rem;
      color: #4a3b2b;
      font-size: 1.2rem;
      font-style: italic;
      background: #87BFD1;
      border-radius: 8px;
    }

    .view-reason-btn {
      background: #2C6E8A;
      color: white;
      border: none;
      padding: 0.4rem 0.8rem;
      border-radius: 4px;
      cursor: pointer;
      font-size: 0.85rem;
      transition: background-color 0.3s;
    }
    
    .view-reason-btn:hover {
      background: #235A73;
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
      background: rgba(44, 110, 138, 0.7);
    }

    .modal-content {
      background: #A9D6E5;
      margin: 5% auto;
      padding: 2rem;
      border-radius: 10px;
      width: 90%;
      max-width: 600px;
      box-shadow: 0 5px 15px rgba(74, 59, 43, 0.5);
      position: relative;
      color: #4a3b2b;
      text-align: left;
    }

    .close-btn {
      position: absolute;
      top: 10px;
      right: 20px;
      color: #2C6E8A;
      font-size: 1.5rem;
      cursor: pointer;
      transition: color 0.3s;
    }

    .close-btn:hover {
      color: #235A73;
    }
    
    .cancellation-details {
      margin-top: 10px;
      padding: 10px;
      background: rgba(255, 255, 255, 0.8);
      border-radius: 5px;
      border-left: 4px solid #2C6E8A;
    }
    
    .cancellation-details p {
      margin: 5px 0;
    }
    
    .reason-label {
      font-weight: bold;
      color: #2C6E8A;
    }
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/partials/header.php'; ?>

  <div class="reports-container">
    <div class="report-filter">
      <div class="filter-item active" data-status="all">All Orders</div>
      <div class="filter-item" data-status="Pending">Pending Orders</div>
      <div class="filter-item" data-status="Approved">Approved Orders</div>
      <div class="filter-item" data-status="Out for Delivery">Out for Delivery</div>
      <div class="filter-item" data-status="Delivered">Delivered Orders</div>
      <div class="filter-item" data-status="Rejected">Rejected Orders</div>
      <div class="filter-item" data-status="Cancelled">Cancelled Orders</div>
    </div>

    <div class="report-table">
      <h2 class="report-title">Order Reports</h2>
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
                      <?php echo htmlspecialchars($order['username']); ?><br>
                      <small><?php echo htmlspecialchars($order['email']); ?></small>
                    </td>
                    <td>
                      <div class="items-list">
                        <?php foreach ($order['items'] as $item): ?>
                          <div class="item-row">
                            <img src="/public/<?php echo htmlspecialchars($item['item_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['item_name']); ?>" 
                                 class="item-img">
                            <span>
                              <?php echo htmlspecialchars($item['item_name']); ?> x 
                              <?php echo htmlspecialchars($item['quantity']); ?>
                              (₱<?php echo number_format($item['price'], 2); ?>)
                            </span>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </td>
                    <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                    <td>
                      <?php if (($order['status'] === 'Rejected' || $order['status'] === 'Cancelled') && !empty($order['cancellation_reason'])): ?>
                        <button class="view-reason-btn" onclick='showCancellationDetails(<?= json_encode($order) ?>)'>
                          View Reason
                        </button>
                      <?php elseif ($order['status'] === 'Delivered'): ?>
                        <button class="view-reason-btn" onclick='showDeliveryDetails(<?= json_encode($order) ?>)'>
                          View Details
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
      <span class="close-btn" onclick="closeCancellationModal()">×</span>
      <h2>Cancellation Details</h2>
      <div class="cancellation-details">
        <p><span class="reason-label">Order ID:</span> <span id="cancel-order-id"></span></p>
        <p><span class="reason-label">Status:</span> <span id="cancel-status"></span></p>
        <p><span class="reason-label">Cancelled By:</span> <span id="cancel-by"></span></p>
        <p><span class="reason-label">Date:</span> <span id="cancel-date"></span></p>
        <p><span class="reason-label">Reason:</span> <span id="cancel-reason"></span></p>
      </div>
    </div>
  </div>
  
  <div id="deliveryModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeDeliveryModal()">×</span>
      <h2>Delivery Details</h2>
      <div class="cancellation-details">
        <p><span class="reason-label">Order ID:</span> <span id="delivery-order-id"></span></p>
        <p><span class="reason-label">Status:</span> <span id="delivery-status"></span></p>
        <p><span class="reason-label">Completed On:</span> <span id="delivery-date"></span></p>
        <p><span class="reason-label">Total Amount:</span> <span id="delivery-amount"></span></p>
        <p><span class="reason-label">Payment Method:</span> <span id="delivery-payment"></span></p>
        <p><span class="reason-label">Customer:</span> <span id="delivery-customer"></span></p>
        <p><span class="reason-label">Contact:</span> <span id="delivery-contact"></span></p>
        <p><span class="reason-label">Delivery Address:</span> <span id="delivery-address"></span></p>
        <p><span class="reason-label">Delivered By:</span> <span id="delivery-rider"></span></p>
        <p><span class="reason-label">Rider Contact:</span> <span id="delivery-rider-contact"></span></p>
        <p><span class="reason-label">Items:</span> <span id="delivery-items"></span></p>
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
  </script>
  
<script src="/public/js/auth.js"></script>
</body>
</html>