/**
 * RentFlow Notifications Module
 * Handles notification polling and display
 */

RentFlow.notifications = {
  /**
   * Poll for notifications from server
   * @param {string} targetId - ID of element to populate with notifications
   * @param {number} limit - Maximum notifications to fetch
   * @param {number} interval - Polling interval in ms (0 = no auto-poll)
   */
  poll: function(targetId, limit = 10, interval = 0) {
    const el = document.getElementById(targetId);
    if (!el) {
      console.error(`Notifications: Element with ID "${targetId}" not found`);
      return;
    }

    this.fetch(targetId, limit);

    // Set up auto-polling if interval is specified
    if (interval > 0) {
      setInterval(() => {
        this.fetch(targetId, limit);
      }, interval);
    }
  },

  /**
   * Fetch notifications from API
   */
  fetch: function(targetId, limit = 10) {
    const el = document.getElementById(targetId);
    if (!el) return;

    fetch(`/api/chat_fetch.php?limit=${limit}`)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP Error: ${response.status}`);
        }
        return response.json();
      })
      .then(items => {
        if (!Array.isArray(items)) {
          console.warn('Notifications: Invalid response format');
          el.innerHTML = '<li>Invalid notification format</li>';
          return;
        }

        el.innerHTML = items.map(n => `
          <li>
            <strong>${RentFlow.ui.escapeHtml(n.title || 'Notification')}</strong>
            <div>${RentFlow.ui.escapeHtml(n.message || '')}</div>
            <small>${RentFlow.ui.escapeHtml(n.created_at || '')}</small>
          </li>
        `).join('');
      })
      .catch(error => {
        console.error('Notifications: Fetch failed:', error);
        el.innerHTML = '<li><em>Failed to load notifications</em></li>';
      });
  }
};

// ========== LEGACY ALIAS (For backward compatibility) ==========
function pollNotifications(targetId, limit = 10) {
  return RentFlow.notifications.poll(targetId, limit);
}

