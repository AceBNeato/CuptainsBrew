<?php
// MySQL Connection
$host = 'localhost';
$db = 'cafe_db'; 
$user = 'root'; 
$pass = ''; 

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch orders from the database
$orders = [];
$sql = "SELECT id, name, description, image_path, order_date, order_time FROM orders"; // Adjust SQL query as needed
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'desc' => $row['description'],
            'image' => $row['image_path'],
            'date' => $row['order_date'],
            'time' => $row['order_time']
        ];
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
  <title>Admin Orders - Cuptain's Brew</title>
  <link rel="icon" href="/images/LOGO.png" sizes="any" />
  <link rel="stylesheet" href="/css/admin-menu.css" />
  <link rel="stylesheet" href="/css/admin-orders.css" />
</head>
<body>

<header class="header">
  <div class="logo-section">
    <img src="/images/LOGO.png" id="logo" alt="Cuptain's Brew Logo" />
  </div>

  <nav class="button-container" id="nav-menu">
    <a href="/views/admin/admin-menu.php" class="nav-button">Menu</a>
    <a href="/views/admin/admin-orders.php" class="nav-button active">Orders</a>
    <a href="/views/admin/admin-reports.php" class="nav-button">Reports</a>
    <a href="/views/admin/admin-accounts.php" class="nav-button">Accounts</a>
    <a id="logout-button" class="nav-button" href="/logout.php">Logout</a>
  </nav>

  <div class="profile-section">
    <span class="vertical-text">Do</span>
  </div>
</header>

<div class="orders-container">
  <table>
    <thead>
      <tr>
        <th>Order Number</th>
        <th>Name of Item</th>
        <th>Order Date</th>
        <th>Order Time</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($orders)): ?>
        <tr>
          <td colspan="5" class="no-orders-message">No orders at the moment.</td>
        </tr>
      <?php else: ?>
        <?php foreach ($orders as $order): ?>
          <tr>
            <td><?= $order['id'] ?></td>
            <td class="order-item">
              <img src="<?= $order['image'] ?>" class="item-img" width="100"
                   onclick="openModal('<?= $order['name'] ?>', '<?= $order['desc'] ?>', '<?= $order['image'] ?>')">
              <?= $order['name'] ?>
            </td>
            <td><?= $order['date'] ?></td>
            <td><?= date("g:i A", strtotime($order['time'])) ?></td>
            <td>
              <button class="accept-btn">Accept</button>
              <button class="decline-btn">Decline</button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal -->
<div id="orderModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h2 id="modal-title"></h2>
    <img id="modal-img" src="" alt="Item Image" width="150">
    <p id="modal-desc"></p>
  </div>
</div>

<script src="/js/admin-menu.js"></script>
<script src="/js/admin-orders.js"></script>
<script src="/js/script.js"></script>
<script src="/js/auth.js"></script>
</body>
</html>
