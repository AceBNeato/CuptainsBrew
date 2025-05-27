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

// Get filter status from GET parameter
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : 'All';
$valid_statuses = ['All', 'Pending', 'Approved', 'Rejected', 'Delivered', 'Out for Delivery', 'Canceled'];
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
    o.payment_method
FROM orders o
WHERE o.user_id = ?";
if ($filter_status !== 'All') {
    $sql .= " AND o.status = ?";
}
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
            'items' => []
        ];
    }

    // Fetch order items for each order
    $sql_items = "SELECT 
        oi.order_id,
        oi.quantity,
        oi.price,
        p.item_name
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id IN (" . implode(',', array_keys($orders)) . ")";
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
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title>My Purchases - Captain's Brew Cafe</title>
  <link rel="icon" href="/public/images/LOGO.png" sizes="any" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
      box-shadow: 0 8px 20px rgba(74, 59, 43, 0.1);
    }

    th, td {
      padding: 1.2rem;
      text-align: left;
      font-size: 0.95rem;
    }

    th {
      background: #87BFD1;
      color: #2C6E8A;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.85rem;
    }

    td {
      color: #4a3b2b;
      border-bottom: 1px solid rgba(74, 59, 43, 0.1);
    }

    tr {
      transition: background-color 0.3s;
    }

    tr:hover {
      background: #A9D6E5;
    }

    .view-items-btn, .mark-delivered-btn, .cancel-order-btn {
      padding: 0.6rem 1.2rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background-color 0.3s, transform 0.2s;
      margin: 0.2rem;
    }

    .view-items-btn {
      background: #4a3b2b;
      color: #fff;
    }

    .view-items-btn:hover {
      background: #3a2b1b;
      transform: translateY(-2px);
    }

    .mark-delivered-btn {
      background: #2C6E8A;
      color: #fff;
    }

    .mark-delivered-btn:hover {
      background: #235A73;
      transform: translateY(-2px);
    }

    .cancel-order-btn {
      background: #d9534f;
      color: #fff;
    }

    .cancel-order-btn:hover {
      background: #c9302c;
      transform: translateY(-2px);
    }

    .no-purchases-message {
      text-align: center;
      padding: 2rem;
      color: #4a3b2b;
      font-size: 1.2rem;
      font-style: italic;
      background: #A9D6E5;
      border-radius: 12px;
      margin-top: 1rem;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: rgba(44, 110, 138, 0.5);
      animation: fadeIn 0.3s ease-in-out;
    }

    .modal-content {
      background: #fff;
      margin: 5% auto;
      padding: 2rem;
      border-radius: 12px;
      width: 90%;
      max-width: 600px;
      box-shadow: 0 10px 30px rgba(74, 59, 43, 0.3);
      position: relative;
      transform: translateY(-20px);
      animation: slideIn 0.3s ease-in-out forwards;
    }

    .close-btn {
      position: absolute;
      top: 15px;
      right: 20px;
      color: #2C6E8A;
      font-size: 1.8rem;
      cursor: pointer;
      transition: color 0.3s;
    }

    .close-btn:hover {
      color: #235A73;
    }

    .modal-content h2 {
      font-size: 1.6rem;
      color: #2C6E8A;
      margin-bottom: 1rem;
    }

    .modal-content p {
      font-size: 0.95rem;
      color: #4a3b2b;
      margin-bottom: 0.8rem;
    }

    .modal-content ul {
      list-style: none;
      margin-bottom: 1rem;
    }

    .modal-content ul li {
      font-size: 0.95rem;
      color: #4a3b2b;
      margin-bottom: 0.6rem;
      padding-left: 1rem;
      position: relative;
    }

    .modal-content ul li::before {
      content: '•';
      color: #2C6E8A;
      position: absolute;
      left: 0;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideIn {
      from { transform: translateY(-20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    @media (max-width: 768px) {
      .header {
        flex-direction: column;
        gap: 1rem;
      }

      .back-btn {
        align-self: flex-start;
      }

      .logo-section {
        margin: 0;
      }

      .purchases-container {
        padding: 1rem;
      }

      .filter-container {
        justify-content: flex-start;
      }

      .filter-select {
        width: 100%;
      }

      .purchases-table {
        font-size: 0.9rem;
      }

      th, td {
        padding: 0.8rem;
        font-size: 0.85rem;
      }

      .purchases-table thead {
        display: none;
      }

      .purchases-table tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid rgba(74, 59, 43, 0.1);
        border-radius: 8px;
        background: #fff;
      }

      .purchases-table td {
        display: block;
        text-align: right;
        padding: 0.5rem 1rem;
        border: none;
        position: relative;
        padding-left: 50%;
      }

      .purchases-table td::before {
        content: attr(data-label);
        position: absolute;
        left: 1rem;
        font-weight: 600;
        color: #2C6E8A;
        text-transform: uppercase;
        font-size: 0.8rem;
      }

      .view-items-btn, .mark-delivered-btn, .cancel-order-btn {
        width: 100%;
        text-align: center;
        margin: 0.5rem 0;
      }

      .modal-content {
        width: 95%;
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <header class="header">
    <div class="logo-section">
      <img src="/public/images/LOGO.png" alt="Captain's Brew Cafe Logo" />
    </div>    
  </header>

  <a href="/views/users/User-Home.php" class="back-btn">
    Back to Home
  </a>

  <div class="purchases-container">
    <h1 class="purchases-title">My Purchases</h1>
    <div class="filter-container">
      <form id="filter-form" method="GET">
        <select name="status" class="filter-select" onchange="this.form.submit()">
          <option value="All" <?= $filter_status === 'All' ? 'selected' : '' ?>>All</option>
          <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
          <option value="Approved" <?= $filter_status === 'Approved' ? 'selected' : '' ?>>Approved</option>
          <option value="Out for Delivery" <?= $filter_status === 'Out for Delivery' ? 'selected' : '' ?>>Out for Delivery</option>
          <option value="Rejected" <?= $filter_status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
          <option value="Canceled" <?= $filter_status === 'Canceled' ? 'selected' : '' ?>>Canceled</option>
          <option value="Delivered" <?= $filter_status === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
        </select>
      </form>
    </div>
    <table class="purchases-table">
      <thead>
        <tr>
          <th>Order Number</th>
          <th>Items</th>
          <th>Order Date</th>
          <th>Order Time</th>
          <th>Status</th>
          <th>Total Amount</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="7" class="no-purchases-message">No purchases found for the selected status.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td data-label="Order Number"><?= htmlspecialchars($order['id']) ?></td>
              <td data-label="Items">
                <button class="view-items-btn" onclick='openItemsModal(<?= json_encode($order) ?>)'>View Items</button>
              </td>
              <td data-label="Order Date"><?= htmlspecialchars($order['date']) ?></td>
              <td data-label="Order Time"><?= htmlspecialchars(date("g:i A", strtotime($order['time']))) ?></td>
              <td data-label="Status"><?= htmlspecialchars($order['status']) ?></td>
              <td data-label="Total Amount">$<?= htmlspecialchars(number_format($order['total_amount'], 2)) ?></td>
              <td data-label="Action">
                <?php if ($order['status'] === 'Out for Delivery'): ?>
                  <button class="mark-delivered-btn" onclick="markDelivered(<?= $order['id'] ?>)">Mark as Delivered</button>
                <?php endif; ?>
                <?php if (in_array($order['status'], ['Pending', 'Approved'])): ?>
                  <button class="cancel-order-btn" onclick="cancelOrder(<?= $order['id'] ?>)">Cancel Order</button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div id="itemsModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeItemsModal()">×</span>
      <h2>Order #<span id="items-order-id"></span> Details</h2>
      <p><strong>Delivery Address:</strong> <span id="items-delivery-address"></span></p>
      <p><strong>Payment Method:</strong> <span id="items-payment-method"></span></p>
      <h3>Items</h3>
      <ul id="items-list"></ul>
    </div>
  </div>

  <script>
    function openItemsModal(order) {
      document.getElementById('items-order-id').textContent = order.id;
      document.getElementById('items-delivery-address').textContent = order.delivery_address || 'N/A';
      document.getElementById('items-payment-method').textContent = order.payment_method || 'N/A';
      
      const itemsList = document.getElementById('items-list');
      itemsList.innerHTML = '';
      order.items.forEach(item => {
        const li = document.createElement('li');
        li.textContent = `${item.item_name} - Quantity: ${item.quantity}, Price: $${item.price}`;
        itemsList.appendChild(li);
      });

      document.getElementById('itemsModal').style.display = 'block';
    }

    function closeItemsModal() {
      document.getElementById('itemsModal').style.display = 'none';
    }

    async function markDelivered(orderId) {
      Swal.fire({
        title: 'Mark as Delivered',
        text: `Confirm that order #${orderId} has been delivered?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, delivered!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          updateOrderStatus(orderId, 'Delivered', () => {
            window.location.reload();
          });
        }
      });
    }

    async function cancelOrder(orderId) {
      Swal.fire({
        title: 'Cancel Order',
        text: `Please select a reason for canceling order #${orderId}:`,
        icon: 'warning',
        input: 'select',
        inputOptions: {
          'Changed my mind': 'Changed my mind',
          'Ordered by mistake': 'Ordered by mistake',
          'Delivery time too long': 'Delivery time too long',
          'Found a better option': 'Found a better option',
          'Other': 'Other'
        },
        inputPlaceholder: 'Select a reason',
        showCancelButton: true,
        confirmButtonText: 'Confirm Cancellation',
        cancelButtonText: 'Cancel',
        inputValidator: (value) => {
          if (!value) {
            return 'You must select a reason!';
          }
        }
      }).then((result) => {
        if (result.isConfirmed) {
          let customReason = '';
          if (result.value === 'Other') {
            Swal.fire({
              title: 'Specify Reason',
              input: 'textarea',
              inputPlaceholder: 'Please provide details for cancellation',
              showCancelButton: true,
              confirmButtonText: 'Submit',
              cancelButtonText: 'Cancel',
              inputValidator: (value) => {
                if (!value) {
                  return 'You must provide a reason!';
                }
              }
            }).then((customResult) => {
              if (customResult.isConfirmed) {
                customReason = customResult.value;
                submitCancellation(orderId, result.value, customReason);
              }
            });
          } else {
            submitCancellation(orderId, result.value, customReason);
          }
        }
      });
    }

    async function submitCancellation(orderId, reason, customReason) {
      try {
        const response = await fetch('/controllers/handle-cancel-order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ orderId, reason, customReason }),
        });

        const result = await response.json();
        if (result.success) {
          Swal.fire({
            icon: 'success',
            title: 'Order Cancelled',
            text: `Order #${orderId} has been cancelled.`,
            timer: 2000,
            showConfirmButton: false
          }).then(() => {
            window.location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: `Failed to cancel order: ${result.message}`
          });
        }
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: `Error: ${error.message}`
        });
      }
    }

    async function updateOrderStatus(orderId, status, callback) {
      try {
        const response = await fetch('/controllers/handle-order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ orderId, action: 'update', status }),
        });

        const result = await response.json();
        if (result.success) {
          Swal.fire({
            icon: 'success',
            title: 'Order Updated',
            text: `Order #${orderId} marked as ${status}`,
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
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: `Error: ${error.message}`
        });
      }
    }

    window.onclick = function(event) {
      const itemsModal = document.getElementById('itemsModal');
      if (event.target === itemsModal) {
        closeItemsModal();
      }
    };
  </script>
</body>
</html>