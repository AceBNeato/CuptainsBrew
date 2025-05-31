<?php
require_once __DIR__ . '/../config.php';

// Start the session if not already started
if (!isset($_SESSION)) {
    session_start();
}

// Set the response header to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => 'An error occurred.'
];

try {
    // Check if the request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    // Validate required fields
    $required_fields = ['firstName', 'lastName', 'mobileNumber', 'email', 'position'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Field '$field' is required.");
        }
    }

    // Validate email
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address.');
    }

    // Check if resume was uploaded
    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Resume upload failed. Please try again.');
    }

    // Validate file type
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $file_type = $_FILES['resume']['type'];
    
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('Only PDF and Word documents are allowed.');
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/../public/uploads/resumes/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate a unique filename
    $filename = uniqid() . '_' . basename($_FILES['resume']['name']);
    $upload_path = $upload_dir . $filename;

    // Move the uploaded file
    if (!move_uploaded_file($_FILES['resume']['tmp_name'], $upload_path)) {
        throw new Exception('Failed to save resume. Please try again.');
    }

    // Prepare data for database
    $first_name = trim($_POST['firstName']);
    $last_name = trim($_POST['lastName']);
    $mobile_number = trim($_POST['mobileNumber']);
    $email = trim($_POST['email']);
    $position = trim($_POST['position']);
    $experience = isset($_POST['experience']) ? trim($_POST['experience']) : '';
    $resume_path = 'public/uploads/resumes/' . $filename;

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO job_applications (first_name, last_name, mobile_number, email, position, resume_path, experience) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $first_name, $last_name, $mobile_number, $email, $position, $resume_path, $experience);

    if (!$stmt->execute()) {
        // Delete the uploaded file if database insertion fails
        unlink($upload_path);
        throw new Exception('Failed to save application: ' . $stmt->error);
    }

    $stmt->close();

    // Set success response
    $response = [
        'success' => true,
        'message' => 'Your application has been submitted successfully!'
    ];

} catch (Exception $e) {
    // Log the error
    error_log("Job application error: " . $e->getMessage(), 3, __DIR__ . '/error.log');
    
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Return JSON response
echo json_encode($response);
exit; 