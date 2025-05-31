<?php
// Include the database configuration
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/rider_auth.php';

// Error logging function
function logRiderError($message) {
    $log_file = __DIR__ . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Ensure session is started
if (!isset($_SESSION)) {
    session_start();
}

// Verify rider is logged in
if (!isset($_SESSION['rider_id']) || !isset($_SESSION['rider_loggedin'])) {
    header('Location: /views/riders/login.php');
    exit();
}

$rider_id = $_SESSION['rider_id'];

try {
    // Fetch rider details
    $rider_query = "SELECT * FROM riders WHERE id = $rider_id";
    $rider_result = $conn->query($rider_query);

    if (!$rider_result) {
        throw new Exception("Database error: " . $conn->error);
    }

    if ($rider_result->num_rows === 0) {
        throw new Exception("Invalid rider account: ID $rider_id not found");
    }

    $rider = $rider_result->fetch_assoc();

    // Fetch assigned orders
    $orders_query = "SELECT o.*, u.username as customer_name, u.contact as customer_contact
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    WHERE o.rider_id = $rider_id
                    AND o.status IN ('Assigned', 'Out for Delivery')
                    ORDER BY o.created_at DESC";
    $orders_result = $conn->query($orders_query);

    if (!$orders_result) {
        throw new Exception("Error fetching active orders: " . $conn->error);
    }

    $orders = [];
    if ($orders_result->num_rows > 0) {
        while ($order = $orders_result->fetch_assoc()) {
            $orders[] = $order;
        }
    }

    // Fetch completed orders (last 7 days)
    $completed_query = "SELECT o.*, u.username as customer_name, u.contact as customer_contact
                       FROM orders o
                       LEFT JOIN users u ON o.user_id = u.id
                       WHERE o.rider_id = $rider_id
                       AND o.status = 'Delivered'
                       AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                       ORDER BY o.created_at DESC";
    $completed_result = $conn->query($completed_query);

    if (!$completed_result) {
        throw new Exception("Error fetching completed orders: " . $conn->error);
    }

    $completed_orders = [];
    if ($completed_result->num_rows > 0) {
        while ($order = $completed_result->fetch_assoc()) {
            $completed_orders[] = $order;
        }
    }

    // Get statistics
    $stats_query = "SELECT 
                   (SELECT COUNT(*) FROM orders WHERE rider_id = $rider_id AND status = 'Assigned') as assigned,
                   (SELECT COUNT(*) FROM orders WHERE rider_id = $rider_id AND status = 'Out for Delivery') as active,
                   (SELECT COUNT(*) FROM orders WHERE rider_id = $rider_id AND status = 'Delivered') as completed,
                   (SELECT SUM(total_amount) FROM orders WHERE rider_id = $rider_id AND status = 'Delivered' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as earnings";
    $stats_result = $conn->query($stats_query);

    if (!$stats_result) {
        throw new Exception("Error fetching statistics: " . $conn->error);
    }

    $stats = $stats_result->fetch_assoc();

} catch (Exception $e) {
    logRiderError($e->getMessage());
    header('Location: /views/riders/login.php?error=' . urlencode('An error occurred. Please try again later.'));
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getCSRFToken() {
    return $_SESSION['csrf_token'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Rider Dashboard - Captain's Brew Cafe</title>
    <link rel="icon" href="/public/images/logo.png" sizes="any" />
    <!-- SweetAlert2 CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Font Awesome CSS CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- SweetAlert2 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom JS -->
    <script src="/public/js/rider-password.js" defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f9fafb;
            color: #4a3b2b;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
            background: #FFFAEE;
            box-shadow: 0 2px 5px rgba(74, 59, 43, 0.2);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo img {
            height: 50px;
        }

        .logo h1 {
            color: #2C6E8A;
            font-size: 1.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-name {
            font-weight: 500;
            color: #4a3b2b;
        }

        .logout-btn, .change-password-btn {
            background: #2C6E8A;
            color: #FFFFFF;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .logout-btn:hover, .change-password-btn:hover {
            background: #235A73;
        }
        
        .change-password-btn {
            background: #4a3b2b;
            margin-right: 0.5rem;
        }
        
        .change-password-btn:hover {
            background: #362c20;
        }

        .main-content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #FFFFFF;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(74, 59, 43, 0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: #2C6E8A;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .section-title {
            color: #2C6E8A;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            border-bottom: 2px solid #A9D6E5;
            padding-bottom: 0.5rem;
        }

        .orders-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .order-card {
            background: #FFFFFF;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(74, 59, 43, 0.1);
            position: relative;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .order-id {
            font-weight: 600;
            color: #2C6E8A;
        }

        .order-date {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .order-details {
            margin-bottom: 1rem;
        }

        .order-detail-item {
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .order-detail-label {
            font-weight: 500;
            color: #4a3b2b;
        }

        .order-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
        }

        .btn-primary {
            background: #2C6E8A;
            color: #FFFFFF;
        }

        .btn-primary:hover {
            background: #235A73;
        }

        .btn-success {
            background: #10B981;
            color: #FFFFFF;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-secondary {
            background: #6B7280;
            color: #FFFFFF;
        }

        .btn-secondary:hover {
            background: #4B5563;
        }

        .status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-assigned {
            background: #FEF3C7;
            color: #D97706;
        }

        .status-active {
            background: #DEF7EC;
            color: #057A55;
        }

        .no-orders {
            text-align: center;
            padding: 2rem;
            background: #FFFFFF;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(74, 59, 43, 0.1);
            color: #6B7280;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .tab {
            padding: 0.5rem 1rem;
            cursor: pointer;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .tab.active {
            background: #2C6E8A;
            color: #FFFFFF;
        }

        .tab:not(.active) {
            background: #E5E7EB;
            color: #4B5563;
        }

        .tab:not(.active):hover {
            background: #D1D5DB;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .user-info {
                flex-direction: column;
                gap: 0.5rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .orders-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">
            <img src="/public/images/logo.png" alt="Captain's Brew Logo">
            <h1>Rider Dashboard</h1>
        </div>
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($rider['name']) ?></span>
            <button class="change-password-btn" id="changePasswordBtn">Change Password</button>
            <form action="/controllers/rider-logout.php" method="POST" id="logout-form">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </header>

    <main class="main-content">
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['assigned'] ?? 0 ?></div>
                <div class="stat-label">Assigned Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['active'] ?? 0 ?></div>
                <div class="stat-label">Out for Delivery</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['completed'] ?? 0 ?></div>
                <div class="stat-label">Completed Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">₱<?= number_format($stats['earnings'] ?? 0, 2) ?></div>
                <div class="stat-label">Earnings (7 days)</div>
            </div>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('active-orders')">Active Orders</div>
            <div class="tab" onclick="switchTab('completed-orders')">Completed Orders</div>
        </div>

        <div id="active-orders" class="tab-content active">
            <h2 class="section-title">Active Orders</h2>
            
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>No active orders assigned to you.</p>
                </div>
            <?php else: ?>
                <div class="orders-container">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <span class="status-badge <?= $order['status'] === 'Assigned' ? 'status-assigned' : 'status-active' ?>">
                                <?= htmlspecialchars($order['status'] ?? '') ?>
                            </span>
                            <div class="order-header">
                                <div class="order-id">Order #<?= htmlspecialchars($order['id'] ?? '') ?></div>
                                <div class="order-date"><?= date('M d, g:i A', strtotime($order['created_at'])) ?></div>
                            </div>
                            <div class="order-details">
                                <div class="order-detail-item">
                                    <span class="order-detail-label">Customer:</span> 
                                    <?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?>
                                </div>
                                <div class="order-detail-item">
                                    <span class="order-detail-label">Contact:</span> 
                                    <?= htmlspecialchars($order['customer_contact'] ?? 'N/A') ?>
                                </div>
                                <div class="order-detail-item">
                                    <span class="order-detail-label">Address:</span> 
                                    <?= htmlspecialchars($order['delivery_address'] ?? 'N/A') ?>
                                </div>
                                <div class="order-detail-item">
                                    <span class="order-detail-label">Amount:</span> 
                                    ₱<?= number_format($order['total_amount'], 2) ?>
                                </div>
                                <div class="order-detail-item">
                                    <span class="order-detail-label">Payment:</span> 
                                    <?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?>
                                </div>
                            </div>
                            <div class="order-actions">
                                <?php if ($order['status'] === 'Assigned'): ?>
                                    <button class="btn btn-primary" onclick="updateOrderStatus(<?= $order['id'] ?>, 'Out for Delivery')">
                                        Start Delivery
                                    </button>
                                <?php elseif ($order['status'] === 'Out for Delivery'): ?>
                                    <button class="btn btn-success" onclick="updateOrderStatus(<?= $order['id'] ?>, 'Delivered')">
                                        Mark as Delivered
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-secondary" onclick="viewOrderItems(<?= $order['id'] ?>)">
                                    View Items
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="completed-orders" class="tab-content">
            <h2 class="section-title">Completed Orders (Last 7 Days)</h2>
            
            <?php if (empty($completed_orders)): ?>
                <div class="no-orders">
                    <p>No completed orders in the last 7 days.</p>
                </div>
            <?php else: ?>
                <div class="orders-container">
                    <?php foreach ($completed_orders as $order): ?>
                        <div class="order-card">
                            <span class="status-badge status-active">Delivered</span>
                            <div class="order-header">
                                <div class="order-id">Order #<?= htmlspecialchars($order['id'] ?? '') ?></div>
                                <div class="order-date"><?= date('M d, g:i A', strtotime($order['created_at'])) ?></div>
                            </div>
                            <div class="order-details">
                                <div class="order-detail-item">
                                    <span class="order-detail-label">Customer:</span> 
                                    <?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?>
                                </div>
                                <div class="order-detail-item">
                                    <span class="order-detail-label">Contact:</span> 
                                    <?= htmlspecialchars($order['customer_contact'] ?? 'N/A') ?>
                                </div>
                                <div class="order-detail-item">
                                    <span class="order-detail-label">Address:</span> 
                                    <?= htmlspecialchars($order['delivery_address'] ?? 'N/A') ?>
                                </div>
                                <div class="order-detail-item">
                                    <span class="order-detail-label">Amount:</span> 
                                    ₱<?= number_format($order['total_amount'], 2) ?>
                                </div>
                            </div>
                            <div class="order-actions">
                                <button class="btn btn-secondary" onclick="viewOrderItems(<?= $order['id'] ?>)">
                                    View Items
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Check for errors on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const errorMessage = urlParams.get('error');
            
            if (errorMessage) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    confirmButtonColor: '#2C6E8A'
                });
                
                // Remove the error parameter from URL
                const url = new URL(window.location);
                url.searchParams.delete('error');
                window.history.replaceState({}, '', url);
            }
        });
        
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Deactivate all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activate the selected tab and content
            document.getElementById(tabId).classList.add('active');
            document.querySelector(`.tab[onclick="switchTab('${tabId}')"]`).classList.add('active');
        }
        
        async function updateOrderStatus(orderId, status) {
            try {
                // Show confirmation dialog
                const result = await Swal.fire({
                    title: 'Update Order Status',
                    text: `Are you sure you want to mark this order as ${status}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2C6E8A',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, update it!'
                });
                
                if (!result.isConfirmed) {
                    return;
                }
                
                // Show loading state
                Swal.fire({
                    title: 'Updating...',
                    text: 'Please wait while we update the order status',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Send update request
                const response = await fetch('/controllers/rider-update-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: status,
                        csrf_token: '<?= getCSRFToken() ?>'
                    }),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Status Updated',
                        text: `Order #${orderId} has been marked as ${status}`,
                        confirmButtonColor: '#2C6E8A'
                    }).then(() => {
                        // Reload the page to reflect changes
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to update order status');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'An error occurred while updating the order status',
                    confirmButtonColor: '#2C6E8A'
                });
            }
        }
        
        async function viewOrderItems(orderId) {
            try {
                // Show loading state
                Swal.fire({
                    title: 'Loading...',
                    text: 'Fetching order items',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Fetch order items
                const response = await fetch(`/controllers/get-order-items.php?order_id=${orderId}`);
                const data = await response.json();
                
                if (data.success) {
                    let itemsHtml = '<ul style="list-style: none; padding: 0;">';
                    
                    data.items.forEach(item => {
                        const variation = item.variation ? ` (${item.variation})` : '';
                        itemsHtml += `
                            <li style="display: flex; align-items: center; margin-bottom: 10px; padding: 10px; background: #f9fafb; border-radius: 6px;">
                                <img src="${item.image || '/public/images/placeholder.jpg'}" alt="${item.item_name}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 10px;">
                                <div>
                                    <div style="font-weight: 500;">${item.item_name}${variation}</div>
                                    <div style="font-size: 0.875rem; color: #6B7280;">
                                        Quantity: ${item.quantity} × ₱${parseFloat(item.price).toFixed(2)}
                                    </div>
                                </div>
                            </li>
                        `;
                    });
                    
                    itemsHtml += '</ul>';
                    
                    Swal.fire({
                        title: `Order #${orderId} Items`,
                        html: itemsHtml,
                        confirmButtonColor: '#2C6E8A',
                        width: '600px'
                    });
                } else {
                    throw new Error(data.message || 'Failed to fetch order items');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'An error occurred while fetching order items',
                    confirmButtonColor: '#2C6E8A'
                });
            }
        }
    </script>
    
    <!-- Password Change Modal -->
    <div id="passwordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h2 style="margin-bottom: 1.5rem; color: #2C6E8A;">Change Password</h2>
            
            <form id="changePasswordForm">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <div style="margin-bottom: 1rem;">
                    <label for="currentPassword" style="display: block; margin-bottom: 0.5rem;">Current Password</label>
                    <div style="position: relative;">
                        <input type="password" id="currentPassword" name="currentPassword" style="width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 6px;" required>
                        <i class="fas fa-eye password-toggle" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #6B7280;" onclick="togglePasswordVisibility('currentPassword', this)"></i>
                    </div>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label for="newPassword" style="display: block; margin-bottom: 0.5rem;">New Password</label>
                    <div style="position: relative;">
                        <input type="password" id="newPassword" name="newPassword" style="width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 6px;" required>
                        <i class="fas fa-eye password-toggle" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #6B7280;" onclick="togglePasswordVisibility('newPassword', this)"></i>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label for="confirmPassword" style="display: block; margin-bottom: 0.5rem;">Confirm New Password</label>
                    <div style="position: relative;">
                        <input type="password" id="confirmPassword" name="confirmPassword" style="width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 6px;" required>
                        <i class="fas fa-eye password-toggle" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #6B7280;" onclick="togglePasswordVisibility('confirmPassword', this)"></i>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: space-between;">
                    <button type="button" id="cancelPasswordChange" style="padding: 0.75rem 1rem; background: #f3f4f6; border: none; border-radius: 6px; cursor: pointer;">Cancel</button>
                    <button type="submit" style="padding: 0.75rem 1rem; background: #2C6E8A; color: white; border: none; border-radius: 6px; cursor: pointer;">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 