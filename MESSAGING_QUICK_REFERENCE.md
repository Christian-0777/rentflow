# Messaging System - Quick Reference

## 🎯 At a Glance

### For Tenants:
```
Profile Page → Chat with Admin Button → Modal → Send Message
                                                     ↓
                                           Admin receives in Messages
                                           Admin replies
                                                     ↓
                                           Tenant sees in Notifications
                                           (+ Email if provided)
```

### For Admins:
```
Navigation → Messages → Select Tenant → View Conversation
                                            ↓
                                       Reply to Message
                                            ↓
                                       Tenant sees in Notifications
                                       (+ Email if enabled)
```

---

## 📁 Key Files Reference

| File | Purpose |
|------|---------|
| `admin/messages.php` | Main messaging interface for admins |
| `tenant/profile.php` | Contains chat button and modal for tenants |
| `api/send_message.php` | Handles message sending and email notifications |
| `api/get_messages.php` | Fetches messages for a conversation |
| `public/assets/css/messenger.css` | All messenger styling |
| `public/assets/js/messenger.js` | Message interaction logic |
| `sql/migration_messages.sql` | Database schema updates |

---

## 🔌 API Quick Reference

### Send Message
```php
POST /api/send_message.php

Parameters:
- receiver_id (required) - User ID to send to
- message (required) - Message text
- sender_email (optional) - Tenant's email
- from_admin (optional) - Set to 1 if admin sending
- from_tenant (optional) - Set to 1 if tenant sending

Response:
{
  "success": true,
  "message_id": 123
}
```

### Get Messages
```php
GET /api/get_messages.php?peer=123&limit=50

Response:
{
  "success": true,
  "messages": [
    {
      "id": 1,
      "sender_id": 5,
      "message": "Hello",
      "created_at": "2026-02-19 10:30:00"
    }
  ]
}
```

---

## CSS Classes Quick Reference

```css
.messenger-container     /* Main wrapper */
.messenger-sidebar      /* Left conversation panel */
.conversation-item      /* Single conversation entry */
.conversation-item.active  /* Selected conversation */
.messages-container     /* Message display area */
.message-group          /* Container for single message */
.message.sent          /* Message sent by user */
.message.received      /* Message received from other */
.chat-input-area       /* Message input section */
.btn-send              /* Send button */
```

---

## JavaScript Functions

### In messenger.js:
```javascript
autoScrollToBottom()        // Auto-scroll to latest
selectTenant(tenantId)     // Select conversation
searchConversations(query) // Search by name
fetchNewMessages(peerId)   // Poll for new messages
formatMessageTime(timestamp) // Format timestamps
```

### In profile.php:
```javascript
openMessageModal()      // Show send message modal
closeMessageModal()     // Hide modal
getAdminId()           // Fetch admin user ID
```

---

## 🗄️ Database Tables

### messages
```sql
- id (PK)
- sender_id (FK → users.id)
- receiver_id (FK → users.id)
- message (TEXT)
- sender_email (VARCHAR 255, optional)
- is_read (TINYINT)
- created_at (DATETIME)
- attachment_path (VARCHAR 255, optional)
- attachment_type (ENUM)
```

### message_threads
```sql
- id (PK)
- user1_id (FK)
- user2_id (FK)
- last_message_id (FK)
- last_message_at (DATETIME)
```

---

## 🎨 Design Colors

```css
Primary Blue:      #667eea
Primary Dark:      #764ba2
Sent Message:      #667eea (blue background, white text)
Received Message:  #e5e5ea (light gray, black text)
Border:            #e0e0e0
Hover:             #f5f5f5
```

---

## ⚡ Performance Tips

1. **Index frequently searched columns:**
   ```sql
   CREATE INDEX idx_messages_sender ON messages(sender_id);
   CREATE INDEX idx_messages_receiver ON messages(receiver_id);
   CREATE INDEX idx_messages_created ON messages(created_at);
   ```

2. **Archive old messages:**
   ```sql
   -- Archive messages older than 1 year
   INSERT INTO messages_archive SELECT * FROM messages 
   WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
   
   DELETE FROM messages 
   WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
   ```

3. **Limit polling frequency** - Currently set to 3 seconds
   - Adjust in `messenger.js` line with `setInterval`

---

## 🔐 Security Checklist

- ✅ Use `require_role()` to verify user type
- ✅ Validate all inputs in API endpoints
- ✅ Use prepared statements for SQL queries
- ✅ Use `htmlspecialchars()` for output
- ✅ Check sender/receiver ownership
- ✅ Verify message belongs to user before operations
- ✅ Validate email format before sending
- ✅ Rate limiting on API endpoints (recommended)

---

## 📧 Email Template Variables

In send_message API, emails use:
- `{senderName}` - Person's full name
- `{message}` - Message content
- `{tenantEmail}` - Tenant's provided email
- `{timestamp}` - Message timestamp
- `{adminName}` - Admin name when replying

---

## 🐛 Common Issues & Fixes

| Issue | Solution |
|-------|----------|
| Messages not loading | Check browser console, verify API path |
| Emails not sending | Check SMTP in config/mailer.php |
| CSS not applying | Hard refresh (Ctrl+Shift+R), check file path |
| Search not working | Verify conversation list is populated |
| Auto-scroll not working | Check messagesContainer ID in HTML |

---

## 📞 How to Add Features

### Add Attachment Support:
1. Update form to accept file input
2. Handle file upload in send_message.php
3. Store path in attachment_path column
4. Display in message template

### Add Message Delete:
1. Create new API endpoint: delete_message.php
2. Check user ownership
3. Soft delete (update deleted_at column)
4. Update UI to show delete icon

### Add Message Edit:
1. Create edit_message.php API
2. Add edited_at column to messages table
3. Show "edited" indicator in UI
4. Log edit history

### Add Typing Indicator:
1. Create typing_status.php API
2. Use WebSockets (optional)
3. Show "Admin is typing..." indicator
4. Timeout after 3 seconds

---

## 🚀 Scaling Considerations

- **High Volume:** Consider message archiving strategy
- **Real-time:** Consider upgrading to WebSockets from polling
- **Storage:** Monitor attachment_path directory size
- **Database:** Regular backups and optimization

---

## 📱 Mobile Responsiveness

Breakpoints in messenger.css:
```css
@media (max-width: 768px)  /* Tablet & phone */
{
  /* Sidebar becomes full-width */
  /* Main chat becomes stacked */
}
```

Test on:
- iPhone (Safari)
- Android (Chrome)
- iPad (Safari)

---

## 🎓 Learning Resources

**For Frontend:**
- Responsive CSS: messenger.css
- Event Handling: messenger.js
- Modal Interactions: profile.php

**For Backend:**
- API Design: send_message.php, get_messages.php
- Database Queries: SQL in PHP files
- Email Handling: Mailer config

**For Database:**
- Schema: migration_messages.sql
- Indexing: Performance tips above
- Relationships: Foreign keys between users

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-02-19 | Initial release |

---

**Quick Reference End** ✅
