<?php
require_once '../../config.php';

// Initialize arrays and fetch orders with joined user and product details
$allOrders = [];
try {
    $sql = "SELECT o.id AS order_id, o.user_id, u.username, u.email, 
                   o.status, o.created_at, o.updated_at,
                   oi.product_id, p.item_name, p.item_image, oi.quantity, oi.price
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.status IN ('Rejected', 'Pending', 'Approved', 'Out for Delivery', 'Delivered')
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
                    'status' => $row['status'],
                    'created_at' => $row['created_at'],
                    'items' => []
                ];
            }
            if ($row['product_id']) {
                $allOrders[$order_id]['items'][] = [
                    'item_name' => $row['item_name'] ?? 'Unknown Item',
                    'item_image' => $row['item_image'] ?? '/public/images/default-item.jpg',
                    'quantity' => $row['quantity'],
                    'price' => $row['price']
                ];
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
  <link rel="icon" href="/images/LOGO.png" sizes="any" />
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
  </style>
</head>
<body>
  <header class="header">
    <div class="logo-section">
      <img src="/public/images/LOGO.png" id="logo" alt="Captain's Brew Cafe Logo" />
    </div>
    <nav class="button-container" id="nav-menu">
      <button class="nav-button" onclick="gotoMenu()">Menu</button>
      <button class="nav-button" onclick="gotoOrders()">Orders</button>
      <button class="nav-button active" onclick="gotoReports()">Reports</button>
      <button class="nav-button" onclick="gotoAccounts()">Accounts</button>
      <a id="logout-button" class="nav-button" href="/logout.php">Logout</a>
    </nav>
  </header>

  <div class="reports-container">
    <div class="report-filter">
      <div class="filter-item active" onclick="filterOrders('all')">All Orders</div>
      <div class="filter-item" onclick="filterOrders('Pending')">Pending Orders</div>
      <div class="filter-item" onclick="filterOrders('Approved')">Approved Orders</div>
      <div class="filter-item" onclick="filterOrders('Out for Delivery')">Out for Delivery</div>
      <div class="filter-item" onclick="filterOrders('Delivered')">Delivered Orders</div>
      <div class="filter-item" onclick="filterOrders('Rejected')">Rejected Orders</div>
    </div>
    <div class="report-table">
      <h2 class="report-title">Order Reports</h2>
      <div id="orders-table">
        <?php
        if (isset($error_message)) {
            echo '<p class="error-message">' . htmlspecialchars($error_message) . '</p>';
        } elseif (empty($allOrders)) {
            echo '<p class="no-reports-message">No orders available for reporting.</p>';
        } else {
            ?>
            <table>
              <thead>
                <tr>
                  <th>Order Number</th>
                  <th>User</th>
                  <th>Ordered Items</th>
                  <th>Order Date</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="orders-body">
                <?php foreach ($allOrders as $order): ?>
                  <tr class="order-row" data-status="<?= htmlspecialchars($order['status']) ?>">
                    <td><?= htmlspecialchars($order['order_id']) ?></td>
                    <td>
                      <?= htmlspecialchars($order['username']) ?><br>
                      <small><?= htmlspecialchars($order['email']) ?></small>
                    </td>
                    <td>
                      <div class="items-list">
                        <?php foreach ($order['items'] as $item): ?>
                          <div class="item-row">
                            <img src="<?= htmlspecialchars($item['item_image']) ?>" class="item-img" alt="Item Image">
                            <span>
                              <?= htmlspecialchars($item['item_name']) ?> 
                              (Qty: <?= htmlspecialchars($item['quantity']) ?>, 
                              Price: $<?= number_format($item['price'], 2) ?>)
                            </span>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </td>
                    <td><?= htmlspecialchars(date('Y-m-d g:i A', strtotime($order['created_at']))) ?></td>
                    <td><?= htmlspecialchars($order['status']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php
        }
        ?>
      </div>
    </div>
  </div>

  <script>
    function gotoMenu() {
      window.location.href = '/views/admin/admin-menu.php';
    }

    function gotoOrders() {
      window.location.href = '/views/admin/admin-orders.php';
    }

    function gotoReports() {
      window.location.href = '/views/admin/admin-reports.php';
    }

    function gotoAccounts() {
      window.location.href = '/views/admin/admin-accounts.php';
    }

    function filterOrders(status) {
      const rows = document.querySelectorAll('.order-row');
      const filterItems = document.querySelectorAll('.filter-item');
      
      filterItems.forEach(item => item.classList.remove('active'));
      document.querySelector(`.filter-item[onclick="filterOrders('${status}')"]`).classList.add('active');

      rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        if (status === 'all' || rowStatus === status) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>