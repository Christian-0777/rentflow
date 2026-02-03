// Register Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
  // Handle OTP verification modal
  const otpForm = document.getElementById('otpForm');
  if (otpForm) {
    otpForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const otpInput = document.getElementById('otpInput');
      const trustDevice = document.getElementById('trustDeviceModal').checked;
      const message = document.getElementById('otpMessage');
      
      if (otpInput.value.length !== 6) {
        message.textContent = 'Please enter a 6-digit code';
        message.style.color = '#d9534f';
        return;
      }
      
      try {
        const response = await fetch(window.location.pathname, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'verify_otp=1&otp=' + otpInput.value + '&trust_device=' + (trustDevice ? '1' : '0')
        });
        
        const result = await response.json();
        
        if (result.success) {
          message.textContent = 'Success! Redirecting...';
          message.style.color = '#5cb85c';
          setTimeout(() => {
            window.location.href = result.redirect;
          }, 1500);
        } else {
          message.textContent = result.message || 'Invalid OTP';
          message.style.color = '#d9534f';
          otpInput.value = '';
          otpInput.focus();
        }
      } catch (error) {
        message.textContent = 'An error occurred. Please try again.';
        message.style.color = '#d9534f';
      }
    });
  }

  // Handle terms checkbox
  const termsCheckbox = document.getElementById('termsCheckbox');
  if (termsCheckbox) {
    termsCheckbox.addEventListener('change', function() {
      const btn = document.getElementById('continueBtn');
      if (this.checked) {
        btn.disabled = false;
        btn.style.backgroundColor = 'var(--primary)';
      } else {
        btn.disabled = true;
        btn.style.backgroundColor = 'gray';
      }
    });
  }

  // Handle 2FA checkbox
  const enable2fa = document.getElementById('enable2fa');
  if (enable2fa) {
    enable2fa.addEventListener('change', function() {
      document.getElementById('enable2faHidden').value = this.checked ? '1' : '0';
    });
  }

  // Handle trust device checkbox
  const trustDevice = document.getElementById('trustDevice');
  if (trustDevice) {
    trustDevice.addEventListener('change', function() {
      document.getElementById('trustDeviceHidden').value = this.checked ? '1' : '0';
    });
  }
});
