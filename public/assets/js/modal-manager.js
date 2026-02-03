/**
 * RentFlow Modal & Interaction System
 * Consolidated modal management and UI interactions
 */

// ========== MODAL MANAGEMENT ==========

/**
 * Open a modal element
 * @param {string|HTMLElement} modalId - ID of the modal or the modal element
 */
function openModal(modalId) {
  const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
  if (modal) {
    modal.classList.add('show');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
  }
}

/**
 * Close a modal element
 * @param {string|HTMLElement} modalId - ID of the modal or the modal element
 */
function closeModal(modalId) {
  const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
  if (modal) {
    modal.classList.remove('show');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
  }
}

/**
 * Toggle modal visibility
 * @param {string|HTMLElement} modalId - ID of the modal or the modal element
 */
function toggleModal(modalId) {
  const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
  if (modal) {
    if (modal.classList.contains('show')) {
      closeModal(modal);
    } else {
      openModal(modal);
    }
  }
}

/**
 * Close modal when clicking outside of it
 */
document.addEventListener('click', function (event) {
  const modals = document.querySelectorAll('.modal.show');
  modals.forEach(modal => {
    if (event.target === modal) {
      closeModal(modal);
    }
  });
});

/**
 * Close modal when pressing Escape key
 */
document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape') {
    const modals = document.querySelectorAll('.modal.show');
    modals.forEach(modal => {
      closeModal(modal);
    });
  }
});

// ========== AUTO-INITIALIZE MODAL BUTTONS ==========

/**
 * Initialize modal buttons (data-modal-trigger and data-modal-close)
 */
document.addEventListener('DOMContentLoaded', function () {
  // Modal trigger buttons
  document.querySelectorAll('[data-modal-trigger]').forEach(button => {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const modalId = this.getAttribute('data-modal-trigger');
      openModal(modalId);
    });
  });

  // Modal close buttons
  document.querySelectorAll('[data-modal-close]').forEach(button => {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      const modalId = this.getAttribute('data-modal-close');
      if (modalId === 'parent') {
        closeModal(this.closest('.modal'));
      } else {
        closeModal(modalId);
      }
    });
  });

  // X close button in modal
  document.querySelectorAll('.modal-close').forEach(button => {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      closeModal(this.closest('.modal'));
    });
  });
});

// ========== IMAGE MODAL ==========

/**
 * Open image in modal
 * @param {string} imagePath - Path to the image
 * @param {string} title - Title for the modal
 */
function openImageModal(imagePath, title = 'Image') {
  let modal = document.getElementById('imageModal');
  
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'imageModal';
    modal.className = 'modal';
    modal.innerHTML = `
      <div class="modal-content" style="max-width: 90%; width: auto; max-height: 90vh;">
        <button class="modal-close">&times;</button>
        <h3 id="imageModalTitle" style="margin-bottom: 16px;">Image</h3>
        <img id="modalImage" src="" alt="Image" style="max-width: 100%; max-height: 70vh; object-fit: contain; border-radius: 8px;">
      </div>
    `;
    document.body.appendChild(modal);
    
    modal.querySelector('.modal-close').addEventListener('click', function () {
      closeModal(modal);
    });
  }
  
  document.getElementById('modalImage').src = imagePath;
  document.getElementById('imageModalTitle').textContent = title;
  openModal(modal);
}

/**
 * Close image modal
 */
function closeImageModal() {
  const modal = document.getElementById('imageModal');
  if (modal) {
    closeModal(modal);
  }
}

// ========== ALERT MANAGEMENT ==========

/**
 * Show an alert message
 * @param {string} message - Message to display
 * @param {string} type - Type: success, danger, warning, info
 * @param {number} duration - Duration in ms (0 = no auto-close)
 */
function showAlert(message, type = 'info', duration = 5000) {
  const alertId = 'alert-' + Date.now();
  const alertClass = `alert alert-${type}`;
  const iconMap = {
    success: 'check_circle',
    danger: 'error',
    warning: 'warning',
    info: 'info'
  };
  
  const alert = document.createElement('div');
  alert.id = alertId;
  alert.className = alertClass;
  alert.innerHTML = `
    <i class="material-icons">${iconMap[type] || 'info'}</i>
    <div>${message}</div>
  `;
  
  document.body.insertBefore(alert, document.body.firstChild);
  
  if (duration > 0) {
    setTimeout(() => {
      alert.remove();
    }, duration);
  }
  
  return alertId;
}

/**
 * Close an alert by ID
 */
function closeAlert(alertId) {
  const alert = document.getElementById(alertId);
  if (alert) {
    alert.remove();
  }
}

// ========== FORM UTILITIES ==========

/**
 * Reset all forms
 */
function resetForms() {
  document.querySelectorAll('form').forEach(form => {
    form.reset();
  });
}

/**
 * Reset a specific form
 */
function resetForm(formId) {
  const form = document.getElementById(formId);
  if (form) {
    form.reset();
  }
}

/**
 * Disable form submission for specific duration
 */
function disableFormSubmit(formId, duration = 3000) {
  const form = document.getElementById(formId);
  if (form) {
    form.style.pointerEvents = 'none';
    form.style.opacity = '0.6';
    setTimeout(() => {
      form.style.pointerEvents = 'auto';
      form.style.opacity = '1';
    }, duration);
  }
}

// ========== TABLE UTILITIES ==========

/**
 * Open modal from table action button
 * @param {string} modalId - Modal ID
 * @param {string} stallNo - Stall number
 * @param {string} type - Stall type
 */
function openApplyModal(stallNo, type = '', modalId = 'applyModal') {
  const modal = document.getElementById(modalId);
  if (modal) {
    const stallNoInput = modal.querySelector('[name="stall_no"]');
    const typeInput = modal.querySelector('[name="type"]');
    
    if (stallNoInput) stallNoInput.value = stallNo;
    if (typeInput) typeInput.value = type;
    
    openModal(modal);
  }
}

/**
 * Open send message modal
 * @param {string} modalId - Modal ID (default: replyModal)
 */
function openReplyModal(modalId = 'replyModal') {
  openModal(modalId);
}

/**
 * Close send message modal
 * @param {string} modalId - Modal ID (default: replyModal)
 */
function closeReplyModal(modalId = 'replyModal') {
  closeModal(modalId);
}

// ========== UTILITY FUNCTIONS ==========

/**
 * Format currency to Philippine Peso
 * @param {number} amount - Amount to format
 */
function formatPeso(amount) {
  return '₱' + parseFloat(amount).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

/**
 * Format date
 * @param {string} dateString - Date string
 * @param {string} format - Format type: short, long, full
 */
function formatDate(dateString, format = 'short') {
  const date = new Date(dateString);
  
  switch(format) {
    case 'long':
      return date.toLocaleDateString('en-PH', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      });
    case 'full':
      return date.toLocaleDateString('en-PH', { 
        weekday: 'long',
        year: 'numeric', 
        month: 'long', 
        day: 'numeric'
      });
    case 'short':
    default:
      return date.toLocaleDateString('en-PH', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric'
      });
  }
}

/**
 * Highlight table row on hover
 */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.table tbody tr').forEach(row => {
    row.addEventListener('mouseenter', function () {
      this.style.backgroundColor = 'var(--primary-light)';
    });
    row.addEventListener('mouseleave', function () {
      this.style.backgroundColor = '';
    });
  });
});

// ========== CONFIRMATION DIALOG ==========

/**
 * Show a confirmation dialog
 * @param {string} message - Confirmation message
 * @param {function} onConfirm - Callback when confirmed
 * @param {function} onCancel - Callback when cancelled
 */
function showConfirm(message, onConfirm, onCancel = null) {
  if (confirm(message)) {
    if (onConfirm) onConfirm();
  } else {
    if (onCancel) onCancel();
  }
}

// ========== EXPORT FUNCTIONS ==========

/**
 * Export table to CSV
 * @param {string} tableId - Table ID
 * @param {string} filename - Output filename
 */
function exportTableToCSV(tableId, filename = 'export.csv') {
  const table = document.getElementById(tableId);
  if (!table) return;
  
  let csv = [];
  const rows = table.querySelectorAll('tr');
  
  rows.forEach(row => {
    const cols = row.querySelectorAll('td, th');
    const csvRow = [];
    cols.forEach(col => {
      csvRow.push('"' + col.innerText.replace(/"/g, '""') + '"');
    });
    csv.push(csvRow.join(','));
  });
  
  const csvContent = csv.join('\n');
  const link = document.createElement('a');
  link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);
  link.download = filename;
  link.click();
}

// ========== RESPONSIVE HELPERS ==========

/**
 * Check if device is mobile
 */
function isMobileDevice() {
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

/**
 * Check if screen is small
 */
function isSmallScreen() {
  return window.innerWidth <= 768;
}

/**
 * Get current breakpoint
 */
function getCurrentBreakpoint() {
  if (window.innerWidth <= 480) return 'xs';
  if (window.innerWidth <= 768) return 'sm';
  if (window.innerWidth <= 1024) return 'md';
  return 'lg';
}
