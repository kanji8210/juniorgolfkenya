/**
 * Junior Golf Kenya Login Form JavaScript
 * Handles password visibility toggle and form interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle
    const togglePasswordBtn = document.querySelector('.jgk-toggle-password');
    const passwordInput = document.getElementById('password');

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            const icon = this.querySelector('.dashicons');
            if (type === 'password') {
                icon.classList.remove('dashicons-hidden');
                icon.classList.add('dashicons-visibility');
                this.setAttribute('aria-label', 'Show password');
            } else {
                icon.classList.remove('dashicons-visibility');
                icon.classList.add('dashicons-hidden');
                this.setAttribute('aria-label', 'Hide password');
            }
        });
    }

    // Add loading state to login button
    const loginForm = document.querySelector('.jgk-login-form-inner');
    const loginBtn = document.querySelector('.jgk-btn-login');

    if (loginForm && loginBtn) {
        loginForm.addEventListener('submit', function() {
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span class="dashicons dashicons-update"></span> Signing In...';
            loginBtn.style.opacity = '0.7';
        });
    }

    // Auto-focus username field
    const usernameInput = document.getElementById('username');
    if (usernameInput && !usernameInput.value) {
        usernameInput.focus();
    }

    // Clear error messages on input
    const inputs = document.querySelectorAll('.jgk-login-form-inner input');
    const errorDiv = document.querySelector('.jgk-login-errors');

    if (errorDiv && inputs.length > 0) {
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                errorDiv.style.transition = 'opacity 0.3s ease';
                errorDiv.style.opacity = '0.5';
            });
        });
    }
});
