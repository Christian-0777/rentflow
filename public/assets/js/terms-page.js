// Terms & Policies Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
  // Handle accept checkbox
  const acceptCheckbox = document.getElementById('acceptCheckbox');
  const acceptBtn = document.getElementById('acceptBtn');
  
  if (acceptCheckbox && acceptBtn) {
    acceptCheckbox.addEventListener('change', function() {
      if (this.checked) {
        acceptBtn.disabled = false;
        acceptBtn.style.backgroundColor = '#0B3C5D';
      } else {
        acceptBtn.disabled = true;
        acceptBtn.style.backgroundColor = 'gray';
      }
    });
  }

  // Enable/Disable Remember Device checkbox based on 2FA checkbox
  const enable2faCheckbox = document.getElementById('enable2fa');
  const rememberDeviceCheckbox = document.getElementById('rememberDevice');

  if (enable2faCheckbox && rememberDeviceCheckbox) {
    enable2faCheckbox.addEventListener('change', function() {
      if (this.checked) {
        rememberDeviceCheckbox.disabled = false;
      } else {
        rememberDeviceCheckbox.disabled = true;
        rememberDeviceCheckbox.checked = false;
      }
    });
  }
});
