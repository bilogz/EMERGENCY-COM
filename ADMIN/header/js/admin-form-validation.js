// ADMIN/header/js/admin-form-validation.js
// Client-side validation for Create Admin form

document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    if (!form) return;

    const fullName = document.getElementById('full_name');
    const username = document.getElementById('username');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const errorBox = document.createElement('div');
    errorBox.className = 'form-text error-text';
    errorBox.style.marginBottom = '1rem';
    form.insertBefore(errorBox, form.firstChild);

    form.addEventListener('submit', function (e) {
        let errors = [];
        errorBox.textContent = '';
        errorBox.style.display = 'none';

        if (fullName && !fullName.value.trim()) {
            errors.push('Full Name is required.');
        }
        if (!username.value.trim()) {
            errors.push('Username is required.');
        }
        if (!email.value.trim()) {
            errors.push('Email is required.');
        } else if (!/^\S+@\S+\.\S+$/.test(email.value.trim())) {
            errors.push('Invalid email format.');
        }
        if (!password.value) {
            errors.push('Password is required.');
        }
        if (!confirmPassword.value) {
            errors.push('Confirm Password is required.');
        }
        if (password.value && confirmPassword.value && password.value !== confirmPassword.value) {
            errors.push('Passwords do not match.');
        }
        if (password.value && password.value.length < 6) {
            errors.push('Password must be at least 6 characters.');
        }

        if (errors.length > 0) {
            e.preventDefault();
            errorBox.textContent = errors.join(' ');
            errorBox.style.display = 'block';
        }
    });
});
