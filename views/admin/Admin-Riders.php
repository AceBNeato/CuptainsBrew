<?php
// Include the database configuration
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

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to get CSRF token
function getCSRFToken() {
    return $_SESSION['csrf_token'];
}

// Fetch riders from database
$riders_query = "SELECT r.*, 
                (SELECT COUNT(*) FROM orders WHERE rider_id = r.id AND status = 'Assigned') as assigned_orders,
                (SELECT COUNT(*) FROM orders WHERE rider_id = r.id AND status = 'Out for Delivery') as active_deliveries,
                (SELECT COUNT(*) FROM orders WHERE rider_id = r.id AND status = 'Delivered') as completed_deliveries
                FROM riders r
                ORDER BY r.created_at DESC";
$riders_result = $conn->query($riders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Rider Management - Captain's Brew Cafe</title>
    <link rel="icon" href="/public/images/LOGO.png" sizes="any" />
    <!-- SweetAlert2 CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .main-content {
            padding: 2rem;
            min-height: calc(100vh - 140px);
        }

        .page-title {
            color: #2C6E8A;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            border-bottom: 2px solid #A9D6E5;
            padding-bottom: 0.5rem;
        }

        .riders-container {
            display: flex;
            gap: 2rem;
        }

        .riders-list {
            flex: 3;
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(74, 59, 43, 0.1);
            padding: 1.5rem;
        }

        .rider-management {
            flex: 2;
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(74, 59, 43, 0.1);
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .section-title {
            color: #2C6E8A;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .add-rider-btn {
            background: #2C6E8A;
            color: #FFFFFF;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .add-rider-btn:hover {
            background: #235A73;
            transform: translateY(-2px);
        }

        .riders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .riders-table th,
        .riders-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }

        .riders-table th {
            background: #F9FAFB;
            color: #4a3b2b;
            font-weight: 500;
        }

        .riders-table tr:hover {
            background: #F9FAFB;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }

        .status-active {
            background: #DEF7EC;
            color: #057A55;
        }

        .status-inactive {
            background: #FDE8E8;
            color: #E02424;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background: #EBF5FF;
            color: #1E40AF;
        }

        .btn-edit:hover {
            background: #DBEAFE;
        }

        .btn-delete {
            background: #FDE8E8;
            color: #E02424;
        }

        .btn-delete:hover {
            background: #FBD5D5;
        }

        .btn-view {
            background: #F3F4F6;
            color: #4B5563;
        }

        .btn-view:hover {
            background: #E5E7EB;
        }

        .form-container {
            margin-top: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            color: #4a3b2b;
        }

        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #2C6E8A;
            box-shadow: 0 0 0 3px rgba(44, 110, 138, 0.1);
        }

        .form-submit {
            background: #2C6E8A;
            color: #FFFFFF;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }

        .form-submit:hover {
            background: #235A73;
        }

        .no-riders {
            text-align: center;
            padding: 2rem;
            color: #6B7280;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .delivery-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            flex: 1;
            background: #F9FAFB;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2C6E8A;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #6B7280;
        }

        .rider-assignments {
            margin-top: 2rem;
        }

        .assignment-card {
            background: #F9FAFB;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #2C6E8A;
        }

        .assignment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .assignment-id {
            font-weight: 500;
            color: #2C6E8A;
        }

        .assignment-time {
            font-size: 0.75rem;
            color: #6B7280;
        }

        .assignment-details {
            font-size: 0.875rem;
            color: #4a3b2b;
        }

        .assignment-status {
            margin-top: 0.5rem;
            font-size: 0.75rem;
        }

        @media (max-width: 768px) {
            .riders-container {
                flex-direction: column;
            }

            .rider-management {
                position: static;
            }

            .delivery-stats {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/partials/header.php'; ?>

    <main class="main-content">
        <h1 class="page-title">Rider Management</h1>
        
        <div class="riders-container">
            <div class="riders-list">
                <div class="section-title">
                    <span>Riders List</span>
                    <button class="add-rider-btn" onclick="openAddRiderModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        Add New Rider
                    </button>
                </div>
                
                <?php if ($riders_result->num_rows > 0): ?>
                <table class="riders-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Assigned</th>
                            <th>Active</th>
                            <th>Completed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($rider = $riders_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $rider['id'] ?></td>
                            <td><?= htmlspecialchars($rider['name']) ?></td>
                            <td><?= htmlspecialchars($rider['contact']) ?></td>
                            <td><?= $rider['assigned_orders'] ?></td>
                            <td><?= $rider['active_deliveries'] ?></td>
                            <td><?= $rider['completed_deliveries'] ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-view" onclick="viewRiderDetails(<?= $rider['id'] ?>)">View</button>
                                    <button class="btn btn-edit" onclick="openEditRiderModal(<?= $rider['id'] ?>, '<?= htmlspecialchars($rider['name']) ?>', '<?= htmlspecialchars($rider['contact']) ?>')">Edit</button>
                                    <button class="btn btn-delete" onclick="confirmDeleteRider(<?= $rider['id'] ?>, '<?= htmlspecialchars($rider['name']) ?>')">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-riders">
                    <p>No riders found. Add your first rider to get started.</p>
                </div>
                <?php endif; ?>
            </div>
            
            
        </div>
    </main>

    <!-- Edit Rider Modal -->
    <div id="editRiderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditRiderModal()">&times;</span>
            <h2 style="margin-bottom: 1rem; color: #2C6E8A;">Edit Rider</h2>
            
            <form id="edit-rider-form" action="/controllers/handle-rider.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit-rider-id" name="rider_id">
                
                <div class="form-group">
                    <label for="edit-rider-name">Rider Name</label>
                    <input type="text" id="edit-rider-name" name="rider_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-rider-contact">Contact Number</label>
                    <input type="text" id="edit-rider-contact" name="rider_contact" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-rider-password">New Password (leave blank to keep current)</label>
                    <div style="position: relative;">
                        <input type="password" id="edit-rider-password" name="rider_password" class="form-control">
                        <i class="fas fa-eye password-toggle" id="toggleEditPassword" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; opacity: 0.7;"></i>
                    </div>
                    <small style="color: #6B7280; font-size: 0.75rem; margin-top: 0.25rem; display: block;">Only fill this if you want to change the rider's password</small>
                </div>
                
                <button type="submit" class="form-submit">Update Rider</button>
            </form>
        </div>
    </div>

    <!-- Add Rider Modal -->
    <div id="addRiderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddRiderModal()">&times;</span>
            <h2 style="margin-bottom: 1rem; color: #2C6E8A;">Add New Rider</h2>
            
            <form id="add-rider-modal-form" action="/controllers/handle-rider.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="modal-rider-name">Rider Name</label>
                    <input type="text" id="modal-rider-name" name="rider_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="modal-rider-contact">Contact Number</label>
                    <input type="text" id="modal-rider-contact" name="rider_contact" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="modal-rider-password">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="modal-rider-password" name="rider_password" class="form-control" required>
                        <i class="fas fa-eye password-toggle" id="toggleAddPassword" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); cursor: pointer; opacity: 0.7;"></i>
                    </div>
                    <small style="color: #6B7280; font-size: 0.75rem; margin-top: 0.25rem; display: block;">Set a password for the rider's account</small>
                </div>
                
                <button type="submit" class="form-submit">Add Rider</button>
            </form>
        </div>
    </div>

    <!-- View Rider Details Modal -->
    <div id="viewRiderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeViewRiderModal()">&times;</span>
            <h2 style="margin-bottom: 1rem; color: #2C6E8A;">Rider Details</h2>
            
            <div id="rider-details-container">
                <!-- Content will be loaded dynamically -->
                <div class="loading" style="text-align: center; padding: 2rem;">Loading...</div>
            </div>
        </div>
    </div>


    <script>
        // Modal functions
        const editRiderModal = document.getElementById('editRiderModal');
        const addRiderModal = document.getElementById('addRiderModal');
        const viewRiderModal = document.getElementById('viewRiderModal');

        function openEditRiderModal(riderId, riderName, riderContact) {
            document.getElementById('edit-rider-id').value = riderId;
            document.getElementById('edit-rider-name').value = riderName;
            document.getElementById('edit-rider-contact').value = riderContact;
            editRiderModal.style.display = 'block';
        }

        function closeEditRiderModal() {
            editRiderModal.style.display = 'none';
        }

        function openAddRiderModal() {
            addRiderModal.style.display = 'block';
        }

        function closeAddRiderModal() {
            addRiderModal.style.display = 'none';
            // Reset form
            document.getElementById('add-rider-modal-form').reset();
        }

        // Password toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add rider password toggle
            const toggleAddPassword = document.getElementById('toggleAddPassword');
            const passwordField = document.getElementById('modal-rider-password');
            
            if (toggleAddPassword && passwordField) {
                toggleAddPassword.addEventListener('click', function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }
            
            // Edit rider password toggle
            const toggleEditPassword = document.getElementById('toggleEditPassword');
            const editPasswordField = document.getElementById('edit-rider-password');
            
            if (toggleEditPassword && editPasswordField) {
                toggleEditPassword.addEventListener('click', function() {
                    const type = editPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    editPasswordField.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }
        });

        async function viewRiderDetails(riderId) {
            viewRiderModal.style.display = 'block';
            const detailsContainer = document.getElementById('rider-details-container');
            detailsContainer.innerHTML = '<div class="loading" style="text-align: center; padding: 2rem;">Loading...</div>';
            
            try {
                const response = await fetch(`/controllers/get-rider-details.php?rider_id=${riderId}`);
                const data = await response.json();
                
                if (data.success) {
                    let html = `
                        <div style="margin-bottom: 1.5rem;">
                            <p><strong>ID:</strong> ${data.rider.id}</p>
                            <p><strong>Name:</strong> ${data.rider.name}</p>
                            <p><strong>Contact:</strong> ${data.rider.contact}</p>
                            <p><strong>Joined:</strong> ${new Date(data.rider.created_at).toLocaleDateString()}</p>
                        </div>
                        
                        <div class="delivery-stats">
                            <div class="stat-card">
                                <div class="stat-value">${data.stats.assigned}</div>
                                <div class="stat-label">Assigned</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">${data.stats.active}</div>
                                <div class="stat-label">Active</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value">${data.stats.completed}</div>
                                <div class="stat-label">Completed</div>
                            </div>
                        </div>
                    `;
                    
                    if (data.assignments.length > 0) {
                        html += `<div class="rider-assignments">
                            <h3 style="margin-bottom: 1rem; color: #2C6E8A;">Current Assignments</h3>`;
                        
                        data.assignments.forEach(order => {
                            html += `
                                <div class="assignment-card">
                                    <div class="assignment-header">
                                        <span class="assignment-id">Order #${order.id}</span>
                                        <span class="assignment-time">${new Date(order.created_at).toLocaleString()}</span>
                                    </div>
                                    <div class="assignment-details">
                                        <p><strong>Customer:</strong> ${order.customer_name}</p>
                                        <p><strong>Address:</strong> ${order.delivery_address}</p>
                                        <p><strong>Amount:</strong> ₱${parseFloat(order.total_amount).toFixed(2)}</p>
                                    </div>
                                    <div class="assignment-status">
                                        <span class="status-badge ${order.status === 'Assigned' ? 'status-inactive' : 'status-active'}">
                                            ${order.status}
                                        </span>
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += `</div>`;
                    } else {
                        html += `<p style="text-align: center; color: #6B7280;">No active assignments</p>`;
                    }
                    
                    detailsContainer.innerHTML = html;
                } else {
                    detailsContainer.innerHTML = `<p style="text-align: center; color: #EF4444;">Error: ${data.message}</p>`;
                }
            } catch (error) {
                detailsContainer.innerHTML = `<p style="text-align: center; color: #EF4444;">Error loading rider details</p>`;
            }
        }

        function closeViewRiderModal() {
            viewRiderModal.style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target === editRiderModal) {
                closeEditRiderModal();
            } else if (event.target === addRiderModal) {
                closeAddRiderModal();
            } else if (event.target === viewRiderModal) {
                closeViewRiderModal();
            }
        }

        // Delete rider confirmation
        function confirmDeleteRider(riderId, riderName) {
            Swal.fire({
                title: 'Delete Rider',
                html: `Are you sure you want to delete <strong>${riderName}</strong>?<br><br>This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create form for POST submission
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '/controllers/handle-rider.php';
                    
                    // Add CSRF token
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    csrfInput.value = '<?= getCSRFToken() ?>';
                    form.appendChild(csrfInput);
                    
                    // Add action
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete';
                    form.appendChild(actionInput);
                    
                    // Add rider ID
                    const riderIdInput = document.createElement('input');
                    riderIdInput.type = 'hidden';
                    riderIdInput.name = 'rider_id';
                    riderIdInput.value = riderId;
                    form.appendChild(riderIdInput);
                    
                    // Append to body and submit
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Check for URL parameters for success/error messages
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const successMessage = urlParams.get('success');
            const errorMessage = urlParams.get('error');

            if (successMessage) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: successMessage,
                    confirmButtonColor: '#2C6E8A'
                });
            } else if (errorMessage) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage,
                    confirmButtonColor: '#2C6E8A'
                });
            }
        });
    </script>
</body>
</html> 