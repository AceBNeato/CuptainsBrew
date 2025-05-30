// Function to toggle password visibility
function togglePasswordVisibility(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password change modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const passwordModal = document.getElementById('passwordModal');
    const cancelPasswordChange = document.getElementById('cancelPasswordChange');
    const changePasswordForm = document.getElementById('changePasswordForm');
    
    if (!changePasswordBtn || !passwordModal || !cancelPasswordChange || !changePasswordForm) {
        return; // Elements not found, exit
    }
    
    // Show modal
    changePasswordBtn.addEventListener('click', () => {
        passwordModal.style.display = 'flex';
    });
    
    // Hide modal
    cancelPasswordChange.addEventListener('click', () => {
        passwordModal.style.display = 'none';
        changePasswordForm.reset();
    });
    
    // Handle form submission
    changePasswordForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        // Validate passwords
        if (newPassword !== confirmPassword) {
            Swal.fire({
                icon: 'error',
                title: 'Password Mismatch',
                text: 'New password and confirmation do not match.',
                confirmButtonColor: '#2C6E8A'
            });
            return;
        }
        
        if (newPassword.length < 6) {
            Swal.fire({
                icon: 'error',
                title: 'Password Too Short',
                text: 'New password must be at least 6 characters long.',
                confirmButtonColor: '#2C6E8A'
            });
            return;
        }
        
        // Get CSRF token
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        
        // Send password change request
        const formData = new FormData();
        formData.append('current_password', currentPassword);
        formData.append('new_password', newPassword);
        formData.append('csrf_token', csrfToken);
        
        fetch('/controllers/rider-change-password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    confirmButtonColor: '#2C6E8A'
                }).then(() => {
                    passwordModal.style.display = 'none';
                    changePasswordForm.reset();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#2C6E8A'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An unexpected error occurred. Please try again.',
                confirmButtonColor: '#2C6E8A'
            });
        });
    });
    
    // Close modal if clicked outside
    passwordModal.addEventListener('click', (e) => {
        if (e.target === passwordModal) {
            passwordModal.style.display = 'none';
            changePasswordForm.reset();
        }
    });
}); 