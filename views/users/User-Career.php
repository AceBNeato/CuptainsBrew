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

        .apply-btn {
            background-color: #2C6E8A;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }

        .apply-btn:hover {
            background-color: #235A73;
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

        <div class="positions-grid">
            <div class="position-card">
                <h3>Barista</h3>
                <p class="salary">₱18,000 - ₱22,000 / month</p>
                <p>Create exceptional coffee experiences for our customers.</p>
                <ul>
                    <li>Craft high-quality coffee beverages</li>
                    <li>Maintain cleanliness standards</li>
                    <li>Provide excellent customer service</li>
                    <li>Work flexible hours</li>
                </ul>
                <button class="apply-btn" onclick="applyPosition('Barista')">Apply Now</button>
            </div>

            <div class="position-card">
                <h3>Shift Supervisor</h3>
                <p class="salary">₱25,000 - ₱30,000 / month</p>
                <p>Lead and inspire our cafe team during your shift.</p>
                <ul>
                    <li>Manage daily operations</li>
                    <li>Train and supervise staff</li>
                    <li>Ensure quality standards</li>
                    <li>Handle customer concerns</li>
                </ul>
                <button class="apply-btn" onclick="applyPosition('Shift Supervisor')">Apply Now</button>
            </div>

            <div class="position-card">
                <h3>Kitchen Staff</h3>
                <p class="salary">₱16,000 - ₱20,000 / month</p>
                <p>Prepare delicious food items for our menu.</p>
                <ul>
                    <li>Follow recipes and procedures</li>
                    <li>Maintain kitchen cleanliness</li>
                    <li>Work in a fast-paced environment</li>
                    <li>Handle food safely</li>
                </ul>
                <button class="apply-btn" onclick="applyPosition('Kitchen Staff')">Apply Now</button>
            </div>
        </div>

        <div class="benefits-section">
            <h2>Benefits & Perks</h2>
            <div class="benefits-grid">
                <div class="benefit-item">
                    <i class="fas fa-coffee"></i>
                    <span>Free Coffee & Meals</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-heart"></i>
                    <span>Health Insurance</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Training & Development</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-clock"></i>
                    <span>Flexible Schedules</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-percentage"></i>
                    <span>Employee Discounts</span>
                </div>
                <div class="benefit-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Career Growth</span>
                </div>
            </div>
        </div>
    </div>

  <!-- Footer -->
  <footer class="footer">
    <?php include '../partials/footer.php'; ?>
  </footer>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script>
        function applyPosition(position) {
            Swal.fire({
                title: 'Apply for ' + position,
                html: `
                    <form id="applicationForm">
                        <input type="text" id="name" class="swal2-input" placeholder="Full Name">
                        <input type="email" id="email" class="swal2-input" placeholder="Email Address">
                        <input type="tel" id="phone" class="swal2-input" placeholder="Phone Number">
                        <textarea id="experience" class="swal2-textarea" placeholder="Brief description of your experience"></textarea>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Submit Application',
                confirmButtonColor: '#2C6E8A',
                cancelButtonColor: '#6c757d',
                preConfirm: () => {
                    const name = document.getElementById('name').value;
                    const email = document.getElementById('email').value;
                    const phone = document.getElementById('phone').value;
                    const experience = document.getElementById('experience').value;

                    if (!name || !email || !phone || !experience) {
                        Swal.showValidationMessage('Please fill in all fields');
                        return false;
                    }

                    return { name, email, phone, experience };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Here you would typically send the application data to your server
                    Swal.fire({
                        icon: 'success',
                        title: 'Application Submitted!',
                        text: 'Thank you for your interest. We will contact you soon!',
                        confirmButtonColor: '#2C6E8A'
                    });
                }
            });
        }
    </script>
</body>
</html> 