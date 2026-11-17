// Reusable password visibility toggle for login and user forms.
// Toggles every password field and eye icon inside the same form,
// so Password and Confirm Password stay in sync.
function toggleVisibility(icon) {
  const form = icon.closest('form') || icon.closest('.input-wrapper');
  const shouldShow = icon.classList.contains('fa-eye');

  form.querySelectorAll('.js-password-field').forEach(function (input) {
    input.type = shouldShow ? 'text' : 'password';
  });

  form.querySelectorAll('.js-password-toggle').forEach(function (toggleIcon) {
    toggleIcon.classList.toggle('fa-eye', !shouldShow);
    toggleIcon.classList.toggle('fa-eye-slash', shouldShow);
  });
}

// Client-side password validation for the Add/Edit User forms.
// Password is optional on the Edit form, so checks only run when set.
function validatePasswords() {
  var form = document.querySelector('.user-form');
  if (!form) return true;
  var password = form.querySelector('input[name="password"]').value || '';
  var confirm = form.querySelector('input[name="confirm"]').value || '';

  if (password && password.length < 4) {
    showToast('Password must be at least 4 characters.', 'warn');
    return false;
  }
  if (password && password !== confirm) {
    showToast('Passwords do not match.', 'warn');
    return false;
  }
  var submitBtn = form.querySelector('button[type="submit"]');
  if (submitBtn) submitBtn.disabled = true;
  return true;
}
