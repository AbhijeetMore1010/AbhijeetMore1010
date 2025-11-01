// admin/login.js
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const errorMessageContainer = document.getElementById('error-message');
    const passwordInput = document.getElementById('password');
    const togglePassword = document.querySelector('.toggle-password');

    // Toggle password visibility
    togglePassword.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.textContent = type === 'password' ? 'visibility_off' : 'visibility';
    });

    // Handle form submission
    loginForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        errorMessageContainer.style.display = 'none'; // Hide previous errors

        const email = document.getElementById('email').value;
        const password = passwordInput.value;

        try {
            const response = await fetch('../api/admin/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email, password }),
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                // Redirect to dashboard on successful login
                window.location.href = 'dashboard.html';
            } else {
                // Display error message from the backend
                errorMessageContainer.textContent = result.message || 'An unknown error occurred.';
                errorMessageContainer.style.display = 'block';
            }
        } catch (error) {
            // Handle network errors
            errorMessageContainer.textContent = 'Failed to connect to the server. Please try again.';
            errorMessageContainer.style.display = 'block';
        }
    });
});
