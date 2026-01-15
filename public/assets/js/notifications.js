// public/assets/js/notification.js
// Polls and displays notifications (latest first) and supports unread badge

function pollNotifications(targetId, limit=10) {
  fetch('/api/chat_fetch.php?limit='+limit)
    .then(r => r.json())
    .then(items => {
      const el = document.getElementById(targetId);
      if (!el) return;
      el.innerHTML = items.map(n => `
        <li>
          <strong>${escapeHtml(n.title || 'Notification')}</strong>
          <div>${escapeHtml(n.message || '')}</div>
          <small>${n.created_at}</small>
        </li>
      `).join('');
    });
}

function escapeHtml(s){return s.replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));}
