// Reset Password Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
  // Get DOM elements
  const resetModal = document.getElementById('resetModal');
  const otpForm = document.getElementById('otpForm');
  const passwordForm = document.getElementById('passwordForm');
  const resendBtn = document.getElementById('resendBtn');
  const cooldownMsg = document.getElementById('cooldownMsg');
  
  // Initialize modal if page loaded with OTP modal
  if (resetModal && resetModal.classList.contains('active')) {
    // Modal is already active from server-side PHP
    setupOTPForm();
  }
  
  // Handle resend button cooldown
  if (resendBtn) {
    resendBtn.addEventListener('click', function(e) {
      e.preventDefault();
      // Cooldown logic handled by server via PHP
      this.form.submit();
    });
  }
  
  function setupOTPForm() {
    if (otpForm) {
      otpForm.addEventListener('submit', function(e) {
        // Form will be submitted normally via POST
      });
    }
  }
  
  // Handle modal close
  const closeButtons = document.querySelectorAll('.modal-close');
  closeButtons.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      if (resetModal) {
        resetModal.classList.remove('active');
      }
    });
  });
});
