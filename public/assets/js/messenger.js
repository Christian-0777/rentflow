/**
 * messenger.js
 * Functionality for messenger-inspired messaging interface
 */

// Auto-scroll messages to bottom on load and when new messages arrive
function autoScrollToBottom() {
  const container = document.getElementById('messagesContainer');
  if (container) {
    setTimeout(() => {
      container.scrollTop = container.scrollHeight;
    }, 100);
  }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
  autoScrollToBottom();
  initializeMessageListener();
});

// Poll for new messages periodically
let messagePollingInterval;

function initializeMessageListener() {
  const tenantIdElem = document.querySelector('input[name="receiver_id"]');
  if (!tenantIdElem) return;

  const tenantId = tenantIdElem.value;
  if (!tenantId) return;

  // Poll for new messages every 3 seconds
  messagePollingInterval = setInterval(() => {
    fetchNewMessages(tenantId);
  }, 3000);
}

function fetchNewMessages(tenantId) {
  fetch(`/rentflow/api/get_messages.php?peer=${tenantId}&limit=50`)
    .then(r => r.json())
    .then(data => {
      if (data.success && data.messages) {
        updateMessagesDisplay(data.messages);
      }
    })
    .catch(err => console.error('Error fetching messages:', err));
}

function updateMessagesDisplay(messages) {
  const container = document.getElementById('messagesContainer');
  if (!container) return;

  // Get current message count
  const currentCount = container.querySelectorAll('.message-group').length;
  
  // If message count changed, reload
  if (messages.length > currentCount) {
    location.reload();
  }
}

// Search conversations
function searchConversations(query) {
  const items = document.querySelectorAll('.conversation-item');
  const lowerQuery = query.toLowerCase();

  items.forEach(item => {
    const nameElem = item.querySelector('.conversation-name');
    if (nameElem) {
      const name = nameElem.textContent.toLowerCase();
      item.style.display = name.includes(lowerQuery) ? 'flex' : 'none';
    }
  });
}

// Enhanced message input - auto-expand textarea
const messageInput = document.getElementById('messageInput');
if (messageInput) {
  messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 100) + 'px';
  });

  // Send message on Ctrl+Enter or Cmd+Enter
  messageInput.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      const form = document.getElementById('messageForm');
      if (form) {
        form.dispatchEvent(new Event('submit'));
      }
    }
  });
}

// Handle real-time message sending
const messageForm = document.getElementById('messageForm');
if (messageForm) {
  messageForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(messageForm);
    const messageInputElem = document.getElementById('messageInput');
    const message = messageInputElem.value.trim();

    if (!message) return;

    try {
      const response = await fetch('/rentflow/api/send_message.php', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        messageInputElem.value = '';
        messageInputElem.style.height = 'auto';
        
        // Reload to show new message
        setTimeout(() => {
          location.reload();
        }, 500);
      } else {
        alert('Error: ' + (data.error || 'Failed to send message'));
      }
    } catch (error) {
      alert('Error: ' + error.message);
    }
  });
}

// Mark conversation as read
function selectTenant(tenantId) {
  // Clear polling interval before navigating
  if (messagePollingInterval) {
    clearInterval(messagePollingInterval);
  }
  window.location.href = `?tenant=${tenantId}`;
}

// Conversation search with live filtering
const searchInput = document.getElementById('conversationSearch');
if (searchInput) {
  searchInput.addEventListener('input', (e) => {
    searchConversations(e.target.value);
  });
}

// Close polling on page unload
window.addEventListener('beforeunload', () => {
  if (messagePollingInterval) {
    clearInterval(messagePollingInterval);
  }
});

// Handle clicks on conversation items
document.querySelectorAll('.conversation-item').forEach(item => {
  item.addEventListener('click', function() {
    document.querySelectorAll('.conversation-item').forEach(i => {
      i.classList.remove('active');
    });
    this.classList.add('active');
  });
});

// Auto-scroll on load
window.addEventListener('load', autoScrollToBottom);

// Format timestamps
function formatMessageTime(timestamp) {
  const date = new Date(timestamp);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return 'now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;
  
  return date.toLocaleDateString();
}
