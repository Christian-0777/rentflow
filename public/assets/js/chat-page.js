// Chat Page JavaScript - Chat Polling and Display

document.addEventListener('DOMContentLoaded', function() {
  const chatThreadElement = document.getElementById('chatThread');
  const userId = getCurrentUserId(); // Assuming this is available globally
  const peerId = getCurrentPeerId(); // Assuming this is available globally

  if (!chatThreadElement) return;

  // Poll chat thread every 2 seconds
  const pollChat = setInterval(() => {
    fetch(`/rentflow/api/chat_fetch.php?peer=${peerId}&limit=50`)
      .then(r => r.json())
      .then(items => {
        chatThreadElement.innerHTML = items.map(i => `
          <div class="chat-item">
            <strong>${i.sender_id == userId ? 'You' : 'Peer'}:</strong>
            <span>${escapeHtml(i.message)}</span>
            <small>${i.created_at}</small>
          </div>
        `).join('');
        
        // Auto-scroll to bottom
        chatThreadElement.scrollTop = chatThreadElement.scrollHeight;
      })
      .catch(err => {
        console.error('Error fetching chat:', err);
      });
  }, 2000);

  // Clean up interval when leaving page
  window.addEventListener('beforeunload', function() {
    clearInterval(pollChat);
  });
});

/**
 * Helper function to escape HTML special characters
 * @param {string} s - String to escape
 * @returns {string} Escaped string
 */
function escapeHtml(s) {
  return s.replace(/[&<>"']/g, m => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  }[m]));
}

/**
 * Get current user ID (should be set globally or passed from server)
 * @returns {number} User ID
 */
function getCurrentUserId() {
  // This should be injected by the server in a script tag
  // For now, try to get from window object
  return window.currentUserId || 0;
}

/**
 * Get current peer ID from URL parameters
 * @returns {number} Peer user ID
 */
function getCurrentPeerId() {
  const params = new URLSearchParams(window.location.search);
  return parseInt(params.get('peer')) || 0;
}
