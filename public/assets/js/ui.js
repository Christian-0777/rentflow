// ui.js - UI interactions for sidebar and mobile menu toggle

document.addEventListener('DOMContentLoaded', function() {
  // Sidebar toggle functionality for mobile
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
});
