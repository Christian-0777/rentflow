/**
 * RentFlow - Unified UI & Interaction System
 * Consolidated from: modal-manager.js, ui.js, and utilities
 * 
 * Usage: window.RentFlow.modal.open('modalId')
 *        window.RentFlow.ui.showAlert('Message', 'success')
 *        window.RentFlow.table.init()
 */

window.RentFlow = window.RentFlow || {
  version: '2.0.0',
  config: {
    animationDuration: 300,
    alertDuration: 5000
  }
};

// ========== MODAL MANAGEMENT ==========
RentFlow.modal = {
  /**
   * Open a modal element
   * @param {string|HTMLElement} modalId - ID of the modal or the modal element
   */
  open: function(modalId) {
    const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
    if (modal) {
      modal.classList.add('show');
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
  },

  /**
   * Close a modal element
   * @param {string|HTMLElement} modalId - ID of the modal or the modal element
   */
  close: function(modalId) {
    const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
    if (modal) {
      modal.classList.remove('show');
      modal.style.display = 'none';
      document.body.style.overflow = 'auto';
    }
  },

  /**
   * Toggle modal visibility
   * @param {string|HTMLElement} modalId - ID of the modal or the modal element
   */
  toggle: function(modalId) {
    const modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
    if (modal) {
      if (modal.classList.contains('show')) {
        this.close(modal);
      } else {
        this.open(modal);
      }
    }
  },

  /**
   * Open image in modal
   * @param {string} imagePath - Path to the image
   * @param {string} title - Title for the modal
   */
  openImageModal: function(imagePath, title = 'Image') {
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
        RentFlow.modal.close(modal);
      });
    }
    
    document.getElementById('modalImage').src = imagePath;
    document.getElementById('imageModalTitle').textContent = title;
    this.open(modal);
  },

  /**
   * Close image modal
   */
  closeImageModal: function() {
    const modal = document.getElementById('imageModal');
    if (modal) {
      this.close(modal);
    }
  },

  /**
   * Initialize modal event listeners
   */
  init: function() {
    // Close modal when clicking outside of it
    document.addEventListener('click', function (event) {
      const modals = document.querySelectorAll('.modal.show');
      modals.forEach(modal => {
        if (event.target === modal) {
          RentFlow.modal.close(modal);
        }
      });
    });

    // Close modal when pressing Escape key
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
          RentFlow.modal.close(modal);
        });
      }
    });

    // Initialize modal trigger buttons (data-modal-trigger)
    document.querySelectorAll('[data-modal-trigger]').forEach(button => {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        const modalId = this.getAttribute('data-modal-trigger');
        RentFlow.modal.open(modalId);
      });
    });

    // Initialize modal close buttons (data-modal-close)
    document.querySelectorAll('[data-modal-close]').forEach(button => {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        const modalId = this.getAttribute('data-modal-close');
        if (modalId === 'parent') {
          RentFlow.modal.close(this.closest('.modal'));
        } else {
          RentFlow.modal.close(modalId);
        }
      });
    });

    // Initialize X close buttons in modals
    document.querySelectorAll('.modal-close').forEach(button => {
      button.addEventListener('click', function (e) {
        e.preventDefault();
        RentFlow.modal.close(this.closest('.modal'));
      });
    });
  }
};

// ========== UI INTERACTIONS ==========
RentFlow.ui = {
  /**
   * Show an alert message
   * @param {string} message - Message to display
   * @param {string} type - Type: success, danger, warning, info
   * @param {number} duration - Duration in ms (0 = no auto-close)
   */
  showAlert: function(message, type = 'info', duration = RentFlow.config.alertDuration) {
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
      <div>${this.escapeHtml(message)}</div>
    `;
    
    document.body.insertBefore(alert, document.body.firstChild);
    
    if (duration > 0) {
      setTimeout(() => {
        alert.remove();
      }, duration);
    }
    
    return alertId;
  },

  /**
   * Close an alert by ID
   */
  closeAlert: function(alertId) {
    const alert = document.getElementById(alertId);
    if (alert) {
      alert.remove();
    }
  },

  /**
   * Escape HTML special characters
   */
  escapeHtml: function(text) {
    return text.replace(/[&<>"']/g, m => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    }[m]));
  },

  /**
   * Show a confirmation dialog
   * @param {string} message - Confirmation message
   * @param {function} onConfirm - Callback when confirmed
   * @param {function} onCancel - Callback when cancelled
   */
  showConfirm: function(message, onConfirm, onCancel = null) {
    if (confirm(message)) {
      if (onConfirm) onConfirm();
    } else {
      if (onCancel) onCancel();
    }
  },

  /**
   * Format currency to Philippine Peso
   * @param {number} amount - Amount to format
   */
  formatPeso: function(amount) {
    return '₱' + parseFloat(amount).toLocaleString('en-PH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  },

  /**
   * Format date
   * @param {string} dateString - Date string
   * @param {string} format - Format type: short, long, full
   */
  formatDate: function(dateString, format = 'short') {
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
  },

  /**
   * Check if device is mobile
   */
  isMobileDevice: function() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  },

  /**
   * Check if screen is small
   */
  isSmallScreen: function() {
    return window.innerWidth <= 768;
  },

  /**
   * Get current breakpoint
   */
  getCurrentBreakpoint: function() {
    if (window.innerWidth <= 480) return 'xs';
    if (window.innerWidth <= 768) return 'sm';
    if (window.innerWidth <= 1024) return 'md';
    return 'lg';
  },

  /**
   * Initialize sidebar toggle functionality for mobile
   */
  initSidebar: function() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (sidebarToggle && sidebar) {
      // Click handler for sidebar toggle
      sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        sidebar.classList.toggle('active');
        
        // Update icon
        if (sidebar.classList.contains('active')) {
          sidebarToggle.innerHTML = '✕';
          sidebarToggle.setAttribute('aria-expanded', 'true');
        } else {
          sidebarToggle.innerHTML = '☰';
          sidebarToggle.setAttribute('aria-expanded', 'false');
        }
      });

      // Close sidebar when a navigation link is clicked
      const navLinks = sidebar.querySelectorAll('a');
      navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          // Only close if navigation changed (check if not current page)
          if (!this.classList.contains('active')) {
            sidebar.classList.remove('active');
            sidebarToggle.innerHTML = '☰';
            sidebarToggle.setAttribute('aria-expanded', 'false');
          }
        });
      });

      // Close sidebar when clicking outside
      document.addEventListener('click', function(event) {
        const isClickInsideSidebar = event.target.closest('.sidebar');
        const isClickOnToggle = event.target.closest('.sidebar-toggle');
        
        if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('active')) {
          sidebar.classList.remove('active');
          sidebarToggle.innerHTML = '☰';
          sidebarToggle.setAttribute('aria-expanded', 'false');
        }
      });

      // Close sidebar when pressing Escape key
      document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && sidebar.classList.contains('active')) {
          sidebar.classList.remove('active');
          sidebarToggle.innerHTML = '☰';
          sidebarToggle.setAttribute('aria-expanded', 'false');
          sidebarToggle.focus();
        }
      });
    }
  },

  /**
   * Highlight table row on hover
   */
  highlightTableRows: function() {
    document.querySelectorAll('.table tbody tr').forEach(row => {
      row.addEventListener('mouseenter', function () {
        this.style.backgroundColor = 'var(--primary-light)';
      });
      row.addEventListener('mouseleave', function () {
        this.style.backgroundColor = '';
      });
    });
  },

  /**
   * Initialize all UI components
   */
  init: function() {
    this.initSidebar();
    this.highlightTableRows();
  }
};

// ========== TABLE MANAGEMENT ==========
RentFlow.table = {
  /**
   * Initialize table sorting
   */
  init: function() {
    document.querySelectorAll('table.table').forEach(table => {
      this.initTable(table);
    });
  },

  /**
   * Initialize a single table for sorting
   */
  initTable: function(table) {
    const headers = table.querySelectorAll('thead th');
    headers.forEach((th, idx) => {
      th.style.cursor = 'pointer';
      th.addEventListener('click', () => RentFlow.table.sortTable(table, idx));
    });
  },

  /**
   * Sort table by column
   */
  sortTable: function(table, colIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const asc = table.dataset.sortAsc === 'true' ? false : true;
    
    rows.sort((a, b) => {
      const av = a.children[colIndex].innerText.trim();
      const bv = b.children[colIndex].innerText.trim();
      const an = parseFloat(av.replace(/[^\d.-]/g, ''));
      const bn = parseFloat(bv.replace(/[^\d.-]/g, ''));
      
      if (!isNaN(an) && !isNaN(bn)) {
        return asc ? an - bn : bn - an;
      }
      return asc ? av.localeCompare(bv) : bv.localeCompare(av);
    });
    
    tbody.innerHTML = '';
    rows.forEach(r => tbody.appendChild(r));
    table.dataset.sortAsc = asc ? 'true' : 'false';
  },

  /**
   * Export table to CSV
   */
  exportToCSV: function(tableId, filename = 'export.csv') {
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
};

// ========== MODAL SHORTCUT FUNCTIONS ==========
RentFlow.modal.openApplyModal = function(stallNo, type = '', modalId = 'applyModal') {
  const modal = document.getElementById(modalId);
  if (modal) {
    const stallNoInput = modal.querySelector('[name="stall_no"]');
    const typeInput = modal.querySelector('[name="type"]');
    
    if (stallNoInput) stallNoInput.value = stallNo;
    if (typeInput) typeInput.value = type;
    
    this.open(modal);
  }
};

RentFlow.modal.openReplyModal = function(modalId = 'replyModal') {
  this.open(modalId);
};

RentFlow.modal.closeReplyModal = function(modalId = 'replyModal') {
  this.close(modalId);
};

// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', function () {
  // Initialize all RentFlow components
  RentFlow.modal.init();
  RentFlow.ui.init();
  RentFlow.table.init();
});

// ========== LEGACY ALIASES (For backward compatibility) ==========
// These functions map to the new namespace for backward compatibility
function openModal(id) { return RentFlow.modal.open(id); }
function closeModal(id) { return RentFlow.modal.close(id); }
function toggleModal(id) { return RentFlow.modal.toggle(id); }
function openImageModal(path, title) { return RentFlow.modal.openImageModal(path, title); }
function closeImageModal() { return RentFlow.modal.closeImageModal(); }
function showAlert(msg, type, duration) { return RentFlow.ui.showAlert(msg, type, duration); }
function closeAlert(id) { return RentFlow.ui.closeAlert(id); }
function formatPeso(amount) { return RentFlow.ui.formatPeso(amount); }
function formatDate(date, format) { return RentFlow.ui.formatDate(date, format); }
function showConfirm(msg, onConfirm, onCancel) { return RentFlow.ui.showConfirm(msg, onConfirm, onCancel); }
function openApplyModal(stall, type, modal) { return RentFlow.modal.openApplyModal(stall, type, modal); }
function openReplyModal(modal) { return RentFlow.modal.openReplyModal(modal); }
function closeReplyModal(modal) { return RentFlow.modal.closeReplyModal(modal); }
function exportTableToCSV(tableId, filename) { return RentFlow.table.exportToCSV(tableId, filename); }
function isMobileDevice() { return RentFlow.ui.isMobileDevice(); }
function isSmallScreen() { return RentFlow.ui.isSmallScreen(); }
function getCurrentBreakpoint() { return RentFlow.ui.getCurrentBreakpoint(); }
