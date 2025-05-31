<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth_check.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Opportunities - Captain's Brew Cafe</title>
    <link rel="icon" href="/public/images/logo.png" sizes="any">
    <!-- Include SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
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
            background: url('/public/images/background/careerbg.png') no-repeat center center fixed;
            background-size: cover;
            color: #4a3b2b;
            min-height: 100vh;
            position: relative;
        }

        a{
            text-decoration: none;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:rgb(51, 50, 50);
            opacity: 0.8;
            z-index: 0;
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .career-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .career-header h1 {
            color: #2C6E8A;
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .career-header p {
            color: #4a3b2b;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .positions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
            margin-bottom: 3rem;
        }

        .position-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .position-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .position-card h3 {
            color: #2C6E8A;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .position-card .salary {
            color: #4CAF50;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .position-card ul {
            list-style-type: none;
            margin: 1rem 0;
        }

        .position-card ul li {
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
        }

        .position-card ul li::before {
            content: '•';
            color: #2C6E8A;
            position: absolute;
            left: 0;
        }

        .benefits-section {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 2rem;
            margin: 3rem 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .benefits-section h2 {
            color: #2C6E8A;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .benefit-item i {
            color: #2C6E8A;
            font-size: 1.5rem;
        }

        /* Application Form */
        .application-section {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 2rem;
            margin: 3rem 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .application-section h2 {
            color: #2C6E8A;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .application-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #4a3b2b;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: #2C6E8A;
            outline: none;
            box-shadow: 0 0 0 2px rgba(44, 110, 138, 0.2);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .file-upload {
            position: relative;
        }

        .file-upload label {
            display: block;
            padding: 0.8rem;
            background: #f5f5f5;
            border: 1px dashed #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload label:hover {
            background: #eee;
            border-color: #2C6E8A;
        }

        .file-upload input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-name {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }

        .submit-btn {
            background-color: #2C6E8A;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }

        .submit-btn:hover {
            background-color: #235A73;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 1rem;
        }

        .checkbox-group input {
            margin-right: 10px;
        }

       /* Footer */
       .footer {
            background-color: var(--dark);
            color: var(--white);
            padding: 5vw 0;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            font-family: 'Segoe UI', sans-serif;
        }

        .footer-col h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-col h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 2px;
            background-color: var(--accent);
        }

        .footer-links li {
            margin-bottom: 10px;
            list-style: none;
        }

        .footer-links a {
            color: var(--white);
            text-decoration: none;
            opacity: 0.8;
            transition: var(--transition);
        }

        .footer-links a:hover {
            opacity: 1;
            color: var(--accent);
            padding-left: 5px;
        }

        .contact-info {
            margin-bottom: 20px;
        }

        .contact-info p {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            opacity: 0.8;
        }

        .contact-info i {
            margin-right: 10px;
            color: var(--accent);
            font-style: normal;
        }

        
        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links img {
            width: 20px;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transition: var(--transition);
        }

        .social-links a:hover {
            background-color: var(--accent);
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            opacity: 0.7;
            font-size: 0.9rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .career-header {
                padding: 1.5rem;
            }

            .career-header h1 {
                font-size: 2rem;
            }

            .positions-grid {
                grid-template-columns: 1fr;
            }
            
            .application-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../users/partials/header.php'; ?>

    <div class="container">
        <div class="career-header">
            <h1>Join Our Team</h1>
            <p>Be part of something special at Captain's Brew Cafe. We're looking for passionate individuals who share our commitment to excellence in coffee and customer service.</p>
        </div>

    

        <div class="application-section">
            <h2>Apply Now</h2>
            <form id="jobApplicationForm" class="application-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="firstName">First Name*</label>
                    <input type="text" id="firstName" name="firstName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="lastName">Last Name*</label>
                    <input type="text" id="lastName" name="lastName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="mobileNumber">Mobile Number*</label>
                    <input type="tel" id="mobileNumber" name="mobileNumber" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="email">E-Mail Address*</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="position">Position Applying For*</label>
                    <select id="position" name="position" class="form-control" required>
                        <option value="">Select a position</option>
                        <option value="Barista">Barista</option>
                        <option value="Kitchen Staff">Kitchen Staff</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="resume">Resume / CV*</label>
                    <div class="file-upload">
                        <label for="resume">Choose File</label>
                        <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
                        <div class="file-name" id="fileName">No file chosen</div>
                    </div>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="experience">Experience & Skills</label>
                    <textarea id="experience" name="experience" class="form-control" placeholder="Tell us about your relevant experience and skills..."></textarea>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the Terms and Conditions</label>
                    </div>
                </div>
                
                <div class="form-group" style="grid-column: 1 / -1;">
                    <button type="submit" class="submit-btn">Submit Application</button>
                </div>
            </form>
        </div>

        
    </div>

    <!-- Footer -->
    <footer class="footer">
        <?php include '../partials/footer.php'; ?>
    </footer>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script>
        // Display file name when selected
        document.getElementById('resume').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
            document.getElementById('fileName').textContent = fileName;
        });

        // Form submission
        document.getElementById('jobApplicationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Create FormData object
            const formData = new FormData(this);
            
            // Show loading state
            Swal.fire({
                title: 'Submitting...',
                text: 'Please wait while we submit your application.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit form via AJAX
            fetch('/controllers/handle-job-application.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Application Submitted!',
                        text: data.message || 'Thank you for your interest in joining Captain\'s Brew Cafe. We will review your application and contact you soon!',
                        confirmButtonColor: '#2C6E8A'
                    });
                    
                    // Reset the form
                    this.reset();
                    document.getElementById('fileName').textContent = 'No file chosen';
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Submission Failed',
                        text: data.message || 'There was an error submitting your application. Please try again.',
                        confirmButtonColor: '#2C6E8A'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Submission Failed',
                    text: 'There was an error submitting your application. Please try again.',
                    confirmButtonColor: '#2C6E8A'
                });
            });
        });
    </script>
    
    <!-- Add auth.js for logout functionality -->
    <script src="/public/js/auth.js"></script>
</body>
</html> 