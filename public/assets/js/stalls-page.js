// Stalls Page JavaScript - Modal Management

document.addEventListener('DOMContentLoaded', function() {
  // Override openApplyModal to use the shared modal manager with form reset
  window.openApplyModal = function(stallNo, type = '', modalId = 'applyModal') {
    const modal = document.getElementById(modalId);
    if (modal) {
      const stallNoInput = modal.querySelector('[name="stall_no"]');
      const typeInput = modal.querySelector('[name="type"]');
      
      if (stallNoInput) stallNoInput.value = stallNo;
      if (typeInput) typeInput.value = type;
      
      openModal(modal);
    }
  };

  // Handle modal close button with form reset
  const applyModal = document.getElementById('applyModal');
  const imageModal = document.getElementById('imageModal');
  
  if (applyModal) {
    const closeBtn = applyModal.querySelector('.modal-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        closeModal(applyModal);
        document.getElementById('applyForm').reset();
      });
    }
  }
  
  if (imageModal) {
    const closeBtn = imageModal.querySelector('.modal-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        closeModal(imageModal);
      });
    }
  }
});
