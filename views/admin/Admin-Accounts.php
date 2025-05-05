

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Admin Orders - Cuptain's Brew</title>
    <link rel="icon" href="/images/LOGO.png" sizes="any" />
    <link rel="stylesheet" href="/public/css/admin-menu.css" />
    <link rel="stylesheet" href="/public/css/orders.css" />
</head>
<body>

<header class="header">
    <div class="logo-section">
        <img src="/public/images/LOGO.png" id="logo" alt="cuptainsbrewlogo" />
    </div>

    <nav class="button-container" id="nav-menu">
      <button class="nav-button" onclick="gotoMenu()">Menu</button>
      <button class="nav-button" onclick="gotoOrders()">Orders</button>
      <button class="nav-button" onclick="gotoReports()">Reports</button>
      <button class="nav-button active" onclick="gotoAccounts()">Accounts</button>
      <a id="logout-button" class="nav-button" href="/logout.php">Logout</a>
    </nav>

  
</header>

<div class="orders-container">
    <?php if (empty($orders)): ?>
        <p class="no-orders-message">No orders at the moment.</p>
    <?php else: ?>
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
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td>
                            <img src="<?= $order['image'] ?>" class="item-img" width="150"
                                onclick="openModal('<?= $order['name'] ?>', '<?= $order['desc'] ?>', '<?= $order['image'] ?>')">
                            <?= $order['name'] ?>
                        </td>
                        <td><?= $order['date'] ?></td>  
                        <td><?= $order['time'] ?></td>
                        <td>
                            <button class="accept-btn">Accept</button>
                            <button class="decline-btn">Decline</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
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

<script src="/public/js/admin-menu.js"></script>
<script src="/public/js/admin-orders.js"></script>
<script src="/public/js/script.js"></script>
<script src="/public/js/auth.js"></script>
</body>
</html>


<?php
// Simulated order data — replace this with a DB query later
$orders = []; // If empty, will show "no orders"
?>
