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

// Initialize arrays and variables
$users = [];
$riders = [];
$error_message = '';
$success_message = '';

// Handle form submissions for add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            throw new Exception('Invalid security token. Please refresh the page and try again.');
        }

        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_rider':
                    handleAddRider($conn);
                    break;
                case 'edit_rider':
                    handleEditRider($conn);
                    break;
                case 'delete_rider':
                    handleDeleteRider($conn);
                    break;
                default:
                    throw new Exception('Invalid action specified.');
            }
        }
    } catch (Exception $e) {
        error_log("Error processing rider action: " . $e->getMessage(), 3, __DIR__ . '/error.log');
        $error_message = $e->getMessage();
    }
}

// Function to handle adding a rider
function handleAddRider($conn) {
    global $success_message, $error_message;
    
            $name = trim($_POST['name'] ?? '');
            $contact = trim($_POST['contact'] ?? '');
            
    validateRiderData($name, $contact);
    
                $stmt = $conn->prepare("INSERT INTO riders (name, contact) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $contact);
    
                if ($stmt->execute()) {
        $success_message = "Rider '$name' added successfully.";
                } else {
        throw new Exception("Failed to add rider: " . $stmt->error);
                }
    
                $stmt->close();
            }

// Function to handle editing a rider
function handleEditRider($conn) {
    global $success_message, $error_message;
    
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $contact = trim($_POST['contact'] ?? '');
            
    if ($id <= 0) {
        throw new Exception('Invalid rider ID.');
    }
    
    validateRiderData($name, $contact);
    
                $stmt = $conn->prepare("UPDATE riders SET name = ?, contact = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $contact, $id);
    
                if ($stmt->execute()) {
        $success_message = "Rider '$name' updated successfully.";
                } else {
        throw new Exception("Failed to update rider: " . $stmt->error);
                }
    
                $stmt->close();
            }

// Function to handle deleting a rider
function handleDeleteRider($conn) {
    global $success_message, $error_message;
    
            $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('Invalid rider ID.');
    }
    
    // Check if rider has any active orders
    $active_orders = 0; // Initialize the variable
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = ? AND status IN ('Assigned', 'Out for Delivery')");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_stmt->bind_result($active_orders);
    $check_stmt->fetch();
    $check_stmt->close();
    
    if ($active_orders > 0) {
        throw new Exception('Cannot delete rider with active orders.');
    }
    
            $stmt = $conn->prepare("DELETE FROM riders WHERE id = ?");
            $stmt->bind_param("i", $id);
    
            if ($stmt->execute()) {
        $success_message = "Rider deleted successfully.";
            } else {
        throw new Exception("Failed to delete rider: " . $stmt->error);
            }
    
            $stmt->close();
        }

// Function to validate rider data
function validateRiderData($name, $contact) {
    if (empty($name)) {
        throw new Exception('Rider name is required.');
    }
    
    if (strlen($name) < 2 || strlen($name) > 50) {
        throw new Exception('Rider name must be between 2 and 50 characters.');
    }
    
    if (empty($contact)) {
        throw new Exception('Contact number is required.');
    }
    
    if (!preg_match('/^[0-9]{10,15}$/', $contact)) {
        throw new Exception('Contact must be a valid phone number (10-15 digits).');
    }
}

// Fetch users with proper error handling
try {
    $sql_users = "SELECT u.id, u.username, u.email, u.is_verified, u.created_at, r.name as role 
                  FROM users u
                  JOIN roles r ON u.role_id = r.id
                  ORDER BY r.name = 'admin' DESC, u.created_at DESC";
    $result_users = $conn->query($sql_users);
    
    if ($result_users === false) {
        throw new Exception("Error fetching users: " . $conn->error);
    }
    
    if ($result_users->num_rows > 0) {
        while ($row = $result_users->fetch_assoc()) {
            $users[] = [
                'id' => $row['id'],
                'username' => $row['username'],
                'email' => $row['email'],
                'role' => $row['role'],
                'is_verified' => $row['is_verified'] ? 'Yes' : 'No',
                'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
            ];
        }
    }

    // Fetch riders with proper error handling
    $sql_riders = "SELECT id, name, contact, created_at 
                   FROM riders 
                   ORDER BY created_at DESC";
    $result_riders = $conn->query($sql_riders);
    
    if ($result_riders === false) {
        throw new Exception("Error fetching riders: " . $conn->error);
    }
    
    if ($result_riders->num_rows > 0) {
        while ($row = $result_riders->fetch_assoc()) {
            $riders[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'contact' => $row['contact'],
                'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
            ];
        }
    }
} catch (Exception $e) {
    error_log("Error fetching accounts: " . $e->getMessage(), 3, __DIR__ . '/error.log');
    $error_message = 'Failed to load accounts. Please try again later.';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Admin Accounts - Captain's Brew Cafe</title>
    <link rel="icon" href="/public/images/LOGO.png" sizes="any" />
    <!-- Add SweetAlert2 CSS CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Add SweetAlert2 JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if ($success_message): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: <?php echo json_encode($success_message); ?>,
                confirmButtonColor: '#2C6E8A'
            });
        });
    </script>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: <?php echo json_encode($error_message); ?>,
                confirmButtonColor: '#2C6E8A'
            });
        });
    </script>
    <?php endif; ?>

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

        /* Accounts Container */
        .accounts-container {
            padding: 2rem;
        }

        .account-filter {
            display: flex;
            background: transparent;
            padding: 1rem;
            border-radius: 10px;
            min-width: 200px;
        }

        .filter-item {
            padding: 1rem;
            margin-right: 1rem;
            border-radius: 10px;
            cursor: pointer;
            color: #4a3b2b;
            font-size: 1rem;
        }

        .filter-item:hover, .filter-item.active {
            background-color: #2C6E8A;
            color: #fff;
        }

        .account-table {
            background: #A9D6E5;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 5px 15px rgba(74, 59, 43, 0.2);
            width: 100%;
        }

        .account-title {
            font-size: 1.5rem;
            color: #2C6E8A;
            margin-bottom: 1rem;
        }

        .add-btn {
            background-color: #2C6E8A;
            color: #fff;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }

        .add-btn:hover {
            background-color: #1A4B6A;
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

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: 0.5rem;
            transition: all 0.3s;
        }

        .edit-btn {
            background-color: #f0ad4e;
            color: #fff;
        }

        .edit-btn:hover {
            background-color: #ec971f;
        }

        .delete-btn {
            background-color: #d9534f;
            color: #fff;
        }

        .delete-btn:hover {
            background-color: #c9302c;
        }

        .no-accounts-message, .error-message, .success-message {
            text-align: center;
            padding: 2rem;
            color: #4a3b2b;
            font-size: 1.2rem;
            font-style: italic;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .error-message {
            background: #f2dede;
        }

        .success-message {
            background: #dff0d8;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .modal-content h2 {
            margin-bottom: 1rem;
            color: #2C6E8A;
        }

        .modal-content label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a3b2b;
        }

        .modal-content input {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #A9D6E5;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .modal-content .btn-group {
            display: flex;
            gap: 1rem;
        }

        .modal-content .action-btn {
            flex: 1;
        }

        .modal-content .cancel-btn {
            background-color: #6c757d;
            color: #fff;
        }

        .modal-content .cancel-btn:hover {
            background-color: #5a6268;
        }

        /* Admin row highlighting */
        .admin-row {
            background-color: rgba(44, 110, 138, 0.1);
            font-weight: 500;
        }
        
        .admin-role {
            background-color: #2C6E8A;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .user-role {
            background-color: #A9D6E5;
            color: #2C6E8A;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/partials/header.php'; ?>

    <div class="accounts-container">
        <div class="account-filter">
            <div class="filter-item active" onclick="showAccounts('users')">Users</div>
            <a href="/views/admin/Admin-Riders.php" class="filter-item" style="text-decoration: none;">Riders</a>
        </div>
        <div class="account-table">
            <h2 class="account-title">Account Management</h2>
            <?php if ($error_message): ?>
                <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <p class="success-message"><?= htmlspecialchars($success_message) ?></p>
            <?php endif; ?>
            <div id="accounts-table">
                <!-- Users Table -->
                <div id="users-table" class="account-section">
                    <?php if (empty($users)): ?>
                        <p class="no-accounts-message">No user accounts available.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Verified</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr class="account-row <?= $user['role'] === 'admin' ? 'admin-row' : '' ?>" data-type="users" data-id="<?= htmlspecialchars($user['id']) ?>">
                                        <td><?= htmlspecialchars($user['id']) ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><span class="<?= $user['role'] === 'admin' ? 'admin-role' : 'user-role' ?>"><?= htmlspecialchars($user['role']) ?></span></td>
                                        <td><?= htmlspecialchars($user['is_verified']) ?></td>
                                        <td><?= htmlspecialchars($user['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Rider Modal -->
    <div id="add-rider-modal" class="modal">
        <div class="modal-content">
            <h2>Add Rider</h2>
            <form id="add-rider-form" method="POST">
                <input type="hidden" name="action" value="add_rider">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <label for="add-name">Name:</label>
                <input type="text" id="add-name" name="name" required>
                <label for="add-contact">Contact:</label>
                <input type="text" id="add-contact" name="contact" required pattern="[0-9]{10,15}">
                <div class="btn-group">
                    <button type="submit" class="action-btn">Save</button>
                    <button type="button" class="action-btn cancel-btn" onclick="closeModal('add-rider-modal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Rider Modal -->
    <div id="edit-rider-modal" class="modal">
        <div class="modal-content">
            <h2>Edit Rider</h2>
            <form id="edit-rider-form" method="POST">
                <input type="hidden" name="action" value="edit_rider">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" id="edit-id" name="id">
                <label for="edit-name">Name:</label>
                <input type="text" id="edit-name" name="name" required>
                <label for="edit-contact">Contact:</label>
                <input type="text" id="edit-contact" name="contact" required pattern="[0-9]{10,15}">
                <div class="btn-group">
                    <button type="submit" class="action-btn">Save</button>
                    <button type="button" class="action-btn cancel-btn" onclick="closeModal('edit-rider-modal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        

        function showAccounts(type) {
            const usersTable = document.getElementById('users-table');
            const ridersTable = document.getElementById('riders-table');
            const filterItems = document.querySelectorAll('.filter-item');

            filterItems.forEach(item => item.classList.remove('active'));
            document.querySelector(`.filter-item[onclick="showAccounts('${type}')"]`).classList.add('active');

            if (type === 'users') {
                usersTable.style.display = 'block';
                ridersTable.style.display = 'none';
            } else {
                usersTable.style.display = 'none';
                ridersTable.style.display = 'block';
            }
        }

        function openAddRiderModal() {
            const modal = document.getElementById('add-rider-modal');
            document.getElementById('add-rider-form').reset();
            modal.style.display = 'flex';
        }

        function openEditRiderModal(id, name, contact) {
            const modal = document.getElementById('edit-rider-modal');
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-contact').value = contact;
            modal.style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function deleteAccount(type, id) {
            if (type !== 'riders') {
                console.error("Only rider accounts can be deleted");
                return;
            }
            
            if (confirm(`Are you sure you want to delete this rider?`)) {
                const formData = new FormData();
                formData.append('action', `delete_${type.slice(0, -1)}`);
                formData.append('id', id);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const row = document.querySelector(`.account-row[data-type="${type}"][data-id="${id}"]`);
                        if (row) row.remove();
                        showMessage('success', data.message);
                    } else {
                        showMessage('error', data.message || 'Failed to delete rider.');
                    }
                })
                .catch(error => showMessage('error', 'Error deleting rider: ' + error));
            }
        }

        // Handle form submissions via AJAX
        document.getElementById('add-rider-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('add-rider-modal');
                    location.reload(); // Refresh to show new rider
                } else {
                    showMessage('error', data.message || 'Failed to add rider.');
                }
            })
            .catch(error => showMessage('error', 'Error adding rider: ' + error));
        });

        document.getElementById('edit-rider-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('edit-rider-modal');
                    location.reload(); // Refresh to show updated rider
                } else {
                    showMessage('error', data.message || 'Failed to update rider.');
                }
            })
            .catch(error => showMessage('error', 'Error updating rider: ' + error));
        });

        function showMessage(type, message) {
            const messageDiv = document.createElement('p');
            messageDiv.className = `${type}-message`;
            messageDiv.textContent = message;
            document.getElementById('accounts-table').prepend(messageDiv);
            setTimeout(() => messageDiv.remove(), 5000);
        }

    </script>
    
<script src="/public/js/auth.js"></script>

</body>
</html>