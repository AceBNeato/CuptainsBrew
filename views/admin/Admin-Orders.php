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

// Fetch orders with product and user details from the database
$orders = [];
$sql = "SELECT o.id, o.user_id, o.total_amount, o.status, o.delivery_address, o.payment_method, 
               o.created_at, o.updated_at, o.rider_id, o.cancellation_reason, o.customer_contact,
               u.username, u.email, u.contact, r.name as rider_name
FROM orders o
LEFT JOIN users u ON o.user_id = u.id
LEFT JOIN riders r ON o.rider_id = r.id
        WHERE o.status NOT IN ('Rejected', 'Cancelled', 'Delivered')
ORDER BY o.created_at DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[$row['id']] = [
            'id' => $row['id'],
            'date' => $row['created_at'],
            'time' => $row['created_at'],
            'status' => $row['status'],
            'user_name' => $row['username'],
            'user_email' => $row['email'],
            'user_contact' => $row['customer_contact'] ? $row['customer_contact'] : $row['contact'],
            'contact_number' => $row['customer_contact'],
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
        
        $order_ids = implode(',', array_keys($orders));
        
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
                if (isset($orders[$order_id])) {
                    $orders[$order_id]['cancellation_reason'] = $cancel['reason'];
                    $orders[$order_id]['cancelled_by'] = $has_is_admin && isset($cancel['is_admin']) && $cancel['is_admin'] ? 'Admin' : 'User';
                    $orders[$order_id]['cancelled_at'] = $cancel['created_at'];
                }
            }
        }
    }

    if (!empty($orders)) {
        $sql_items = "SELECT 
            oi.order_id,
            oi.quantity,
            oi.price,
            oi.variation,
            p.item_name,
            p.item_image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id IN (" . implode(',', array_keys($orders)) . ")";
        
        $items_result = $conn->query($sql_items);
        
        if ($items_result && $items_result->num_rows > 0) {
            while ($item = $items_result->fetch_assoc()) {
                $image_path = !empty($item['item_image']) ? '/public/' . ltrim($item['item_image'], '/') : '';
                $orders[$item['order_id']]['items'][] = [
                    'item_name' => $item['item_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'variation' => $item['variation'],
                    'image' => $image_path
                ];
            }
        }
    }
} else {
    if ($conn->error) {
        error_log("Error fetching orders: " . $conn->error, 3, __DIR__ . '/error.log');
    }
}

$riders = [];
$sql_riders = "SELECT id, name FROM riders";
$riders_result = $conn->query($sql_riders);
if ($riders_result && $riders_result->num_rows > 0) {
    while ($rider = $riders_result->fetch_assoc()) {
        $riders[] = $rider;
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
  <title>Admin Orders - Captain's Brew Cafe</title>
  <link rel="icon" href="/public/images/LOGO.png" sizes="any" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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

    .orders-container {
      display: flex;
      justify-content: center;
      padding: 2rem;
    }

    table {
      width: 100%;
      max-width: 1500px;
      border-collapse: collapse;
      background: #A9D6E5;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(74, 59, 43, 0.2);
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

    .order-item {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .item-img {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.3s;
    }

    .item-img:hover {
      transform: scale(1.05);
    }

    .review-btn, .reject-btn, .assign-btn, .notify-btn {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 0.9rem;
      margin: 0 0.3rem;
      transition: background-color 0.3s;
    }

    .review-btn {
      background: #2C6E8A;
      color: #fff;
    }

    .review-btn:hover {
      background: #235A73;
    }

    .reject-btn {
      background: #4a3b2b;
      color: #fff;
    }

    .reject-btn:hover {
      background: #3a2b1b;
    }

    .assign-btn {
      background: #2C6E8A;
      color: #fff;
    }

    .assign-btn:hover {
      background: #235A73;
    }

    .notify-btn {
      background: #2C6E8A;
      color: #fff;
    }

    .notify-btn:hover {
      background: #235A73;
    }

    .no-orders-message {
      text-align: center;
      padding: 2rem;
      color: #4a3b2b;
      font-size: 1.2rem;
      font-style: italic;
      background: #87BFD1;
      border-radius: 8px;
    }

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

    .modal-content h2 {
      font-size: 1.5rem;
      color: #2C6E8A;
      margin-bottom: 1rem;
    }

    .modal-content img {
      width: 150px;
      height: 150px;
      object-fit: cover;
      border-radius: 8px;
      margin-bottom: 1rem;
    }

    .modal-content p {
      font-size: 0.9rem;
      color: #4a3b2b;
      margin-bottom: 0.5rem;
    }

    .modal-content ul {
      list-style: none;
      margin-bottom: 1rem;
    }

    .modal-content ul li {
      font-size: 0.9rem;
      margin-bottom: 0.5rem;
    }

    .modal-content select, .modal-content button {
      padding: 0.5rem;
      margin: 0.5rem 0;
      border-radius: 5px;
      border: none;
      font-size: 0.9rem;
    }

    .modal-content select {
      width: 100%;
      background: #fff;
      color: #4a3b2b;
    }

    .modal-content button {
      background: #2C6E8A;
      color: #fff;
      cursor: pointer;
      transition: background-color 0.3s;
    }

    .modal-content button:hover {
      background: #235A73;
    }

    .modal-content .reject-btn {
      background: #4a3b2b;
      color: #fff;
    }

    .modal-content .reject-btn:hover {
      background: #3a2b1b;
    }
    
    .reason-tag {
      display: inline-block;
      margin-top: 5px;
      padding: 3px 8px;
      background: #2C6E8A;
      color: white;
      border-radius: 4px;
      font-size: 0.8rem;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    
    .reason-tag:hover {
      background: #235A73;
    }
    
    .reason-tag i {
      margin-right: 3px;
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
    
    .cancellation-details .reason-label {
      font-weight: bold;
      color: #2C6E8A;
    }
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/partials/header.php'; ?>

  <div class="orders-container">
    <table>
      <thead>
        <tr>
          <th>Order Number</th>
          <th>Name of Item</th>
          <th>Order Date</th>
          <th>Order Time</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="6" class="no-orders-message">No orders at the moment.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($orders as $order): ?>
            <tr id="order-<?= htmlspecialchars($order['id']) ?>">
              <td><?= htmlspecialchars($order['id']) ?></td>
              <td class="order-item">
                <?php if ($order['items'] && !empty($order['items'][0]['image'])): ?>
                  <img src="<?= htmlspecialchars($order['items'][0]['image']) ?>" class="item-img" width="100"
                       onerror="this.src='/public/images/placeholder.jpg';"
                       onclick="openModal('<?= htmlspecialchars($order['items'][0]['item_name'] ?? 'N/A') ?>', '<?= htmlspecialchars($order['items'][0]['variation'] ?? 'N/A') ?>', '<?= htmlspecialchars($order['items'][0]['image'] ?? '') ?>')">
                <?php else: ?>
                  <img src="/public/images/placeholder.jpg" class="item-img" width="100">
                <?php endif; ?>
                <?= htmlspecialchars($order['items'][0]['item_name'] ?? 'N/A') ?>
              </td>
              <td><?= htmlspecialchars($order['date']) ?></td>
              <td><?= htmlspecialchars(date("g:i A", strtotime($order['time']))) ?></td>
              <td>
                <?= htmlspecialchars($order['status']) ?>
                <?php if (($order['status'] === 'Rejected' || $order['status'] === 'Cancelled') && !empty($order['cancellation_reason'])): ?>
                  <div class="reason-tag" onclick="showCancellationDetails(<?= json_encode($order) ?>)">
                    <i class="fas fa-info-circle"></i> View Reason
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($order['status'] === 'Pending'): ?>
                  <button class="review-btn" onclick='openReviewModal(<?= json_encode($order) ?>)'>Review</button>
                  <button class="reject-btn" onclick="rejectOrder(<?= $order['id'] ?>)">Reject</button>
                <?php elseif ($order['status'] === 'Approved'): ?>
                  <button class="assign-btn" onclick='openAssignRiderModal(<?= json_encode($order) ?>, <?= json_encode($riders) ?>)'>Assign Rider</button>
                <?php elseif ($order['status'] === 'Assigned'): ?>
                  <button class="notify-btn" onclick="notifyUser(<?= $order['id'] ?>)">Notify Out for Delivery</button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div id="orderModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal()">×</span>
      <h2 id="modal-title"></h2>
      <img id="modal-img" src="" alt="Item Image">
      <p id="modal-desc"></p>
    </div>
  </div>

  <div id="reviewModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeReviewModal()">×</span>
      <h2>Order #<span id="review-order-id"></span></h2>
      <p><strong>Customer:</strong> <span id="review-user-name"></span></p>
      <p><strong>Email:</strong> <span id="review-user-email"></span></p>
      <p><strong>Contact:</strong> <span id="review-user-contact"></span></p>
      <p><strong>Delivery Address:</strong> <span id="review-delivery-address"></span></p>
      <p><strong>Payment Method:</strong> <span id="review-payment-method"></span></p>
      <p><strong>Total Amount:</strong> $<span id="review-total-amount"></span></p>
      <h3>Items</h3>
      <ul id="review-items"></ul>
      <button class="review-btn" onclick="approveOrder()">Approve</button>
    </div>
  </div>

  <div id="assignRiderModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeAssignRiderModal()">×</span>
      <h2>Assign Rider for Order #<span id="assign-order-id"></span></h2>
      <label for="rider-select">Select Rider:</label>
      <select id="rider-select"></select>
      <button onclick="assignRider()">Assign</button>
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

  <script>
    let currentOrderId = null;
    let riders = <?= json_encode($riders) ?>;

    function openModal(name, desc, image) {
      document.getElementById('modal-title').textContent = name;
      document.getElementById('modal-desc').textContent = desc;
      document.getElementById('modal-img').src = image || '/public/images/placeholder.jpg';
      document.getElementById('orderModal').style.display = 'block';
    }

    function closeModal() {
      document.getElementById('orderModal').style.display = 'none';
    }

    function openReviewModal(order) {
      currentOrderId = order.id;
      document.getElementById('review-order-id').textContent = order.id;
      document.getElementById('review-user-name').textContent = order.user_name || 'N/A';
      document.getElementById('review-user-email').textContent = order.user_email || 'N/A';
      document.getElementById('review-user-contact').textContent = order.user_contact || 'N/A';
      document.getElementById('review-delivery-address').textContent = order.delivery_address || 'N/A';
      document.getElementById('review-payment-method').textContent = order.payment_method || 'N/A';
      document.getElementById('review-total-amount').textContent = order.total_amount || '0.00';
      
      const itemsList = document.getElementById('review-items');
      itemsList.innerHTML = '';
      order.items.forEach(item => {
        const li = document.createElement('li');
        li.textContent = `${item.item_name} - Quantity: ${item.quantity}, Price: $${item.price}`;
        itemsList.appendChild(li);
      });

      document.getElementById('reviewModal').style.display = 'block';
    }

    function closeReviewModal() {
      document.getElementById('reviewModal').style.display = 'none';
      currentOrderId = null;
    }

    function openAssignRiderModal(order, riders) {
      currentOrderId = order.id;
      document.getElementById('assign-order-id').textContent = order.id;
      const riderSelect = document.getElementById('rider-select');
      riderSelect.innerHTML = '<option value="">Select a Rider</option>';
      riders.forEach(rider => {
        const option = document.createElement('option');
        option.value = rider.id;
        option.textContent = rider.name;
        riderSelect.appendChild(option);
      });
      document.getElementById('assignRiderModal').style.display = 'block';
    }

    function closeAssignRiderModal() {
      document.getElementById('assignRiderModal').style.display = 'none';
      currentOrderId = null;
    }

    async function approveOrder() {
      if (!currentOrderId) return;
      updateOrderStatus(currentOrderId, 'Approved', () => {
        closeReviewModal();
        window.location.reload();
      });
    }

    async function rejectOrder(orderId) {
      Swal.fire({
        title: 'Reject Order',
        html: `
          <div style="text-align: left;">
            <p>Please provide a reason for rejecting order #${orderId}:</p>
            <select id="reject-reason" class="swal2-select" style="width: 100%; margin-bottom: 10px;">
              <option value="">-- Select a reason --</option>
              <option value="Out of stock">Out of stock</option>
              <option value="Invalid address">Invalid address</option>
              <option value="Duplicate order">Duplicate order</option>
              <option value="Payment issue">Payment issue</option>
              <option value="Other">Other (specify below)</option>
            </select>
            <textarea id="custom-reason" class="swal2-textarea" placeholder="Additional details or custom reason..." style="display: none; width: 100%;"></textarea>
          </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Reject Order',
        cancelButtonText: 'Cancel',
        didOpen: () => {
          const selectEl = document.getElementById('reject-reason');
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
          const reason = document.getElementById('reject-reason').value;
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
          
          // Call the cancel order endpoint
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
                title: 'Order Rejected',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
              }).then(() => {
                // Remove the row after rejection
            const orderRow = document.getElementById(`order-${orderId}`);
            if (orderRow) {
              orderRow.remove();
              const tbody = document.querySelector('tbody');
              if (tbody.children.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="no-orders-message">No orders at the moment.</td></tr>';
              }
            }
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to reject order'
              });
            }
          })
          .catch(error => {
            console.error('Error:', error);
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'An unexpected error occurred'
            });
          });
        }
      });
    }

    async function rejectOrderFromModal() {
      if (!currentOrderId) return;
      closeReviewModal();
      rejectOrder(currentOrderId);
    }

    async function assignRider() {
      const riderId = document.getElementById('rider-select').value;
      if (!currentOrderId || !riderId) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Please select a rider'
        });
        return;
      }
      updateOrderStatus(currentOrderId, 'Assigned', () => {
        closeAssignRiderModal();
        window.location.reload();
      }, riderId);
    }

    async function notifyUser(orderId) {
      Swal.fire({
        title: 'Notify User',
        text: `Send notification for order #${orderId} as Out for Delivery?`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Yes, notify!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          updateOrderStatus(orderId, 'Out for Delivery', () => {
            Swal.fire({
              icon: 'success',
              title: 'Notification Sent',
              text: `User notified for order #${orderId}`,
              timer: 2000,
              showConfirmButton: false
            });
            // Remove the row after setting status to Out for Delivery
            const orderRow = document.getElementById(`order-${orderId}`);
            if (orderRow) {
              orderRow.remove();
              const tbody = document.querySelector('tbody');
              if (tbody.children.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="no-orders-message">No orders at the moment.</td></tr>';
              }
            }
          }, null, true);
        }
      });
    }

    async function updateOrderStatus(orderId, status, callback, riderId = null, notify = false) {
      try {
        const payload = { orderId, action: 'update', status };
        if (riderId) payload.riderId = riderId;
        if (notify) payload.notify = true;

        const response = await fetch('/controllers/handle-order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(payload),
        });

        // Check if response is ok before trying to parse JSON
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }

        // Get the raw text first to check for valid JSON
        const responseText = await response.text();
        let result;
        
        try {
          // Try to parse the response as JSON
          result = JSON.parse(responseText);
        } catch (parseError) {
          console.error("JSON Parse Error:", parseError);
          throw new Error(`Invalid JSON response: ${responseText.substring(0, 100)}...`);
        }

        if (result.success) {
          Swal.fire({
            icon: 'success',
            title: 'Status Updated',
            text: `Order #${orderId} status updated to ${status}`,
            timer: 2000,
            showConfirmButton: false
          }).then(callback);
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: `Failed to update order: ${result.message}`
          });
        }
      } catch (error) {
        console.error("Error in updateOrderStatus:", error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: `Error: ${error.message}`
        });
      }
    }

    function gotoMenu() {
      window.location.href = '/views/admin/Admin-Menu.php';
    }

    function gotoOrders() {
      window.location.href = '/views/admin/Admin-Orders.php';
    }

    function gotoReports() {
      window.location.href = '/views/admin/Admin-Reports.php';
    }

    function gotoAccounts() {
      window.location.href = '/views/admin/Admin-Accounts.php';
    }

    function showCancellationDetails(order) {
      document.getElementById('cancel-order-id').textContent = order.id;
      document.getElementById('cancel-status').textContent = order.status;
      document.getElementById('cancel-by').textContent = order.cancelled_by || 'Unknown';
      
      const cancelDate = order.cancelled_at ? new Date(order.cancelled_at) : new Date(order.updated_at);
      document.getElementById('cancel-date').textContent = cancelDate.toLocaleString();
      
      document.getElementById('cancel-reason').textContent = order.cancellation_reason || 'No reason provided';
      
      document.getElementById('cancellationModal').style.display = 'block';
    }
    
    function closeCancellationModal() {
      document.getElementById('cancellationModal').style.display = 'none';
    }

    window.onclick = function(event) {
      const orderModal = document.getElementById('orderModal');
      const reviewModal = document.getElementById('reviewModal');
      const assignRiderModal = document.getElementById('assignRiderModal');
      const cancellationModal = document.getElementById('cancellationModal');
      
      if (event.target === orderModal) {
        closeModal();
      }
      if (event.target === reviewModal) {
        closeReviewModal();
      }
      if (event.target === assignRiderModal) {
        closeAssignRiderModal();
      }
      if (event.target === cancellationModal) {
        closeCancellationModal();
      }
    };
  </script>
  <script src="/public/js/auth.js"></script>
</body>
</html>