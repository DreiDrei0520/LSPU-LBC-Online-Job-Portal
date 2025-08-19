 document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const loader = document.getElementById('loader');
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            // Toggle password visibility
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? 
                        '<i class="fas fa-eye text-gray-400 hover:text-gray-600" aria-hidden="true"></i>' :
                        '<i class="fas fa-eye-slash text-gray-400 hover:text-gray-600" aria-hidden="true"></i>';
                });
            }
            
            // Form submission handler
            if (loginForm) {
                loginForm.addEventListener('submit', function() {
                    // Show loading indicator
                    if (loginButton && loader) {
                        loginButton.disabled = true;
                        loader.style.display = 'inline-block';
                        const span = loginButton.querySelector('span');
                        if (span) span.textContent = 'Signing in...';
                    }
                });
            }
            
            // Focus on email field by default
            const emailField = document.getElementById('email');
            if (emailField) {
                emailField.focus();
            }
            
            // Add floating animation to the auth container
            const authContainer = document.querySelector('.auth-container');
            if (authContainer) {
                authContainer.classList.add('animate__animated', 'animate__fadeInUp');
            }
        });