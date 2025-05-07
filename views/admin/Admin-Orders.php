<?php
require_once '../../config.php';

// Fetch orders from the database
$orders = [];
$sql = "SELECT id, name, description, image_path, order_date, order_time FROM orders";
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
  <title>Admin Orders - Captain's Brew Cafe</title>
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

    .profile-section {
      margin-left: auto;
    }

    .vertical-text {
      font-size: 1rem;
      color: #4a3b2b;
      writing-mode: vertical-rl;
      text-orientation: mixed;
    }

    /* Orders Container */
    .orders-container {
      display: flex;
      justify-content: center;
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
      color:rgb(57, 46, 32);
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

    .accept-btn, .decline-btn {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background-color 0.3s;
      margin: 0 0.3rem;
    }

    .accept-btn {
      background: #2C6E8A;
      color: #fff;
    }

    .accept-btn:hover {
      background: #235A73;
    }

    .decline-btn {
      background: #4a3b2b;
      color: #fff;
    }

    .decline-btn:hover {
      background: #3a2b1b;
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

    /* Modal */
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
      max-width: 450px;
      box-shadow: 0 5px 15px rgba(74, 59, 43, 0.5);
      position: relative;
      color: #4a3b2b;
      text-align: center;
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
      <button class="nav-button active" onclick="gotoOrders()">Orders</button>
      <button class="nav-button" onclick="gotoReports()">Reports</button>
      <button class="nav-button" onclick="gotoAccounts()">Accounts</button>
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
            <tr id="order-<?= $order['id'] ?>">
              <td><?= htmlspecialchars($order['id']) ?></td>
              <td class="order-item">
                <img src="<?= htmlspecialchars($order['image']) ?>" class="item-img" width="100"
                     onclick="openModal('<?= htmlspecialchars($order['name']) ?>', '<?= htmlspecialchars($order['desc']) ?>', '<?= htmlspecialchars($order['image']) ?>')">
                <?= htmlspecialchars($order['name']) ?>
              </td>
              <td><?= htmlspecialchars($order['date']) ?></td>
              <td><?= htmlspecialchars(date("g:i A", strtotime($order['time']))) ?></td>
              <td>
                <button class="accept-btn" onclick="handleOrder(<?= $order['id'] ?>, 'accept')">Accept</button>
                <button class="decline-btn" onclick="handleOrder(<?= $order['id'] ?>, 'decline')">Decline</button>
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
      <span class="close-btn" onclick="closeModal()">×</span>
      <h2 id="modal-title"></h2>
      <img id="modal-img" src="" alt="Item Image">
      <p id="modal-desc"></p>
    </div>
  </div>

  <script>
    function openModal(name, desc, image) {
      document.getElementById('modal-title').textContent = name;
      document.getElementById('modal-desc').textContent = desc;
      document.getElementById('modal-img').src = image;
      document.getElementById('orderModal').style.display = 'block';
    }

    function closeModal() {
      document.getElementById('orderModal').style.display = 'none';
    }

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

    async function handleOrder(orderId, action) {
      try {
        const response = await fetch('/controllers/handle-order.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ orderId, action }),
        });

        const result = await response.json();
        if (result.success) {
          const orderRow = document.getElementById(`order-${orderId}`);
          orderRow.remove();
          
          const tbody = document.querySelector('tbody');
          if (tbody.children.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="no-orders-message">No orders at the moment.</td></tr>';
          }
        } else {
          alert(`Failed to ${action} order: ${result.message}`);
        }
      } catch (error) {
        alert(`Error: ${error.message}`);
      }
    }

    window.onclick = function(event) {
      const modal = document.getElementById('orderModal');
      if (event.target === modal) {
        closeModal();
      }
    };
  </script>
</body>
</html>