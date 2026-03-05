// 2FA OTP Input Handler
document.addEventListener('DOMContentLoaded', function() {
  // Only input numbers in OTP field
  const otpInput = document.querySelector('.otp-input');
  if (otpInput) {
    otpInput.addEventListener('input', function(e) {
      e.target.value = e.target.value.replace(/[^0-9]/g, '');
    });
  }
});
