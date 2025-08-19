        document.addEventListener('DOMContentLoaded', function() {
            const registerForm = document.getElementById('registerForm');
            const registerButton = document.getElementById('registerButton');
            const loader = document.getElementById('loader');
            const togglePassword1 = document.getElementById('togglePassword1');
            const togglePassword2 = document.getElementById('togglePassword2');
            const passwordInput1 = document.getElementById('password');
            const passwordInput2 = document.getElementById('confirm_password');
            
            // Toggle password visibility
            if (togglePassword1 && passwordInput1) {
                togglePassword1.addEventListener('click', function() {
                    const type = passwordInput1.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput1.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? 
                        '<i class="fas fa-eye text-gray-400 hover:text-gray-600" aria-hidden="true"></i>' :
                        '<i class="fas fa-eye-slash text-gray-400 hover:text-gray-600" aria-hidden="true"></i>';
                });
            }
            
            if (togglePassword2 && passwordInput2) {
                togglePassword2.addEventListener('click', function() {
                    const type = passwordInput2.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput2.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? 
                        '<i class="fas fa-eye text-gray-400 hover:text-gray-600" aria-hidden="true"></i>' :
                        '<i class="fas fa-eye-slash text-gray-400 hover:text-gray-600" aria-hidden="true"></i>';
                });
            }
            
            // Form submission handler
            if (registerForm) {
                registerForm.addEventListener('submit', function() {
                    // Show loading indicator
                    if (registerButton && loader) {
                        registerButton.disabled = true;
                        loader.style.display = 'inline-block';
                        const span = registerButton.querySelector('span');
                        if (span) span.textContent = 'Registering...';
                    }
                });
            }
            
            // Focus on first name field by default
            const fnameField = document.getElementById('fname');
            if (fnameField) {
                fnameField.focus();
            }
            
            // Add floating animation to the auth container
            const authContainer = document.querySelector('.auth-container');
            if (authContainer) {
                authContainer.classList.add('animate__animated', 'animate__fadeInUp');
            }
        });