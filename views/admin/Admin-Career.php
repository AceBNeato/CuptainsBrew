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

// Initialize variables
$applications = [];
$error_message = '';
$success_message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            throw new Exception('Invalid security token. Please refresh the page and try again.');
        }

        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        
        if ($id <= 0) {
            throw new Exception('Invalid application ID.');
        }
        
        $allowed_statuses = ['Pending', 'Reviewed', 'Shortlisted', 'Rejected', 'Hired'];
        if (!in_array($status, $allowed_statuses)) {
            throw new Exception('Invalid status.');
        }
        
        $stmt = $conn->prepare("UPDATE job_applications SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            $success_message = "Application status updated successfully.";
        } else {
            throw new Exception("Failed to update status: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error updating application status: " . $e->getMessage(), 3, __DIR__ . '/error.log');
        $error_message = $e->getMessage();
    }
}

// Fetch job applications
try {
    $sql = "SELECT * FROM job_applications ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    if ($result === false) {
        throw new Exception("Error fetching applications: " . $conn->error);
    }
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $applications[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching applications: " . $e->getMessage(), 3, __DIR__ . '/error.log');
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Applications - Admin Dashboard</title>
    <link rel="icon" href="/public/images/logo.png" sizes="any">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #2C6E8A;
            --primary-dark: #235A73;
            --primary-light: #A9D6E5;
            --secondary: #4A3B2B;
            --secondary-light: #FFFAEE;
            --secondary-lighter: #FFDBB5;
            --accent: #ffb74a;
            --white: #fff;
            --dark: #1a1310;
            --text: #333333;
            --shadow-light: 0 2px 5px rgba(74, 59, 43, 0.2);
            --shadow-medium: 0 4px 8px rgba(44, 110, 138, 0.2);
            --shadow-dark: 0 5px 15px rgba(74, 59, 43, 0.5);
            --border-radius: 10px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: var(--text);
        }

        .applications-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .applications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .applications-title {
            color: var(--primary-dark);
            font-size: 1.8rem;
        }

        .applications-count {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
        }

        .applications-table {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-light);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: var(--primary);
            color: var(--white);
            font-weight: 500;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 500;
            text-align: center;
            min-width: 100px;
        }

        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .status-reviewed {
            background-color: #DBEAFE;
            color: #1E40AF;
        }

        .status-shortlisted {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .status-rejected {
            background-color: #FEE2E2;
            color: #B91C1C;
        }

        .status-hired {
            background-color: #C7D2FE;
            color: #3730A3;
        }

        .action-btn {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: var(--transition);
        }

        .action-btn:hover {
            background-color: var(--primary-dark);
        }

        .view-btn {
            background-color: var(--accent);
        }

        .view-btn:hover {
            background-color: #e69626;
        }

        .error-message {
            background-color: #FEE2E2;
            color: #B91C1C;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .success-message {
            background-color: #D1FAE5;
            color: #065F46;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #eee;
        }

        .modal-title {
            font-size: 1.5rem;
            color: var(--primary-dark);
        }

        .close-modal {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text);
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--primary);
        }

        .application-details {
            margin-bottom: 1.5rem;
        }

        .detail-group {
            margin-bottom: 1rem;
        }

        .detail-label {
            font-weight: 500;
            margin-bottom: 0.25rem;
            color: var(--primary-dark);
        }

        .detail-value {
            color: var(--text);
        }

        .status-form {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(44, 110, 138, 0.2);
        }

        .btn-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .no-applications {
            text-align: center;
            padding: 2rem;
            color: var(--text);
            font-style: italic;
        }

        @media (max-width: 768px) {
            .applications-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            th, td {
                padding: 0.75rem 0.5rem;
            }

            .applications-table {
                overflow-x: auto;
            }

            table {
                min-width: 600px;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/partials/header.php'; ?>

    <div class="applications-container">
        <div class="applications-header">
            <h1 class="applications-title">Job Applications</h1>
            <span class="applications-count"><?php echo count($applications); ?> Applications</span>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <div class="applications-table">
            <?php if (empty($applications)): ?>
                <div class="no-applications">No job applications found.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Applied On</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['id']); ?></td>
                                <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['position']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                                        <?php echo htmlspecialchars($app['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn view-btn" onclick="viewApplication(<?php echo $app['id']; ?>)">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Application Details Modal -->
    <div id="application-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Application Details</h2>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div id="application-details" class="application-details">
                <!-- Application details will be loaded here -->
            </div>
            <form id="status-form" class="status-form" method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" id="application-id" name="id" value="">
                
                <div class="form-group">
                    <label for="status">Update Status:</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="Pending">Pending</option>
                        <option value="Reviewed">Reviewed</option>
                        <option value="Shortlisted">Shortlisted</option>
                        <option value="Rejected">Rejected</option>
                        <option value="Hired">Hired</option>
                    </select>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="action-btn">Update Status</button>
                    <button type="button" class="action-btn" style="background-color: #6B7280;" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Application data
        const applications = <?php echo json_encode($applications); ?>;
        
        // View application details
        function viewApplication(id) {
            const app = applications.find(a => a.id == id);
            if (!app) return;
            
            const detailsContainer = document.getElementById('application-details');
            const statusSelect = document.getElementById('status');
            const applicationIdInput = document.getElementById('application-id');
            
            // Set application ID in the form
            applicationIdInput.value = app.id;
            
            // Set current status in the dropdown
            statusSelect.value = app.status;
            
            // Build details HTML
            let detailsHTML = `
                <div class="detail-group">
                    <div class="detail-label">Full Name</div>
                    <div class="detail-value">${app.first_name} ${app.last_name}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Email</div>
                    <div class="detail-value">${app.email}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Mobile Number</div>
                    <div class="detail-value">${app.mobile_number}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Position</div>
                    <div class="detail-value">${app.position}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Applied On</div>
                    <div class="detail-value">${new Date(app.created_at).toLocaleString()}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Resume</div>
                    <div class="detail-value">
                        <a href="/${app.resume_path}" target="_blank" class="action-btn">View Resume</a>
                    </div>
                </div>
            `;
            
            if (app.experience) {
                detailsHTML += `
                    <div class="detail-group">
                        <div class="detail-label">Experience & Skills</div>
                        <div class="detail-value">${app.experience}</div>
                    </div>
                `;
            }
            
            detailsContainer.innerHTML = detailsHTML;
            
            // Show modal
            document.getElementById('application-modal').style.display = 'flex';
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('application-modal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('application-modal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Handle form submission via AJAX
        document.getElementById('status-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Reload the page to show updated status
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update status. Please try again.');
            });
        });
    </script>
</body>
</html> 