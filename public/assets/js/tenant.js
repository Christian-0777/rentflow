// tenant.js
// JavaScript for tenant pages

// Common functions for tenant pages
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="material-icons">${type === 'success' ? 'check_circle' : 'error'}</i>
        <div>${message}</div>
        <button class="btn-close" onclick="this.parentElement.style.display='none'"></button>
    `;
    document.querySelector('.page-header').after(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
}

function confirmAction(message) {
    return confirm(message);
}

// Navigation active state
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.tenant-navbar-nav a');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
});