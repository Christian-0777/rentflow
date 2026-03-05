# Messaging System Rework - Complete Guide

## Overview

The messaging system has been completely restructured and redesigned with a **Messenger-inspired layout**. This new system provides a modern, intuitive interface for communications between admins and tenants.

---

## 🎯 Key Changes

### 1. **New Database Tables**
A new `messages` table has been created to handle all messaging functionality separately from notifications.

**Migration File:** `sql/migration_messages.sql`

**New Tables:**
- `messages` - Stores all messages between admin and tenants
- `message_threads` - Tracks conversations for organizing chats

**Key Fields:**
- `sender_id` - ID of the message sender
- `receiver_id` - ID of the message receiver
- `message` - Message content
- `sender_email` - Optional email provided by tenant
- `is_read` - Message read status
- `attachment_path` - File attachment path
- `created_at` - Timestamp

---

## 👥 User Flows

### **For Admins:**

#### Accessing Messages:
1. Go to **Admin Dashboard**
2. Click **Messages** in the navigation (new menu item)
3. See list of all conversations with tenants (left sidebar)
4. Select a tenant to view full conversation
5. Type and send replies directly

#### Features:
- ✅ Messenger-like conversation list (left panel)
- ✅ Full message thread display (center)
- ✅ Real-time search conversations
- ✅ Unread message indicators
- ✅ Read receipts
- ✅ Easy-to-read message history

---

### **For Tenants:**

#### Accessing Chat:
1. Go to **Profile** page
2. Scroll to bottom
3. Click **"Chat with Admin"** button
4. Modal appears with message form

#### Message Modal Features:
- Optional email field (if provided, admin's replies sent to email)
- Message textarea
- Send and Cancel buttons
- Professional modal design

#### Message Delivery:
- ✅ Message goes to admin in Messages interface
- ✅ Admin can reply
- ✅ Tenant receives reply in **Notifications**
- ✅ If email provided, tenant also receives email notification

---

## 📁 File Structure

### New/Modified Files:

```
/admin/messages.php                    → New messenger interface for admin
/tenant/profile.php                    → Updated with chat modal button
/tenant/notifications.php              → Simplified (removed old chat)
/admin/notifications.php               → Updated navigation, removed chat section

/api/send_message.php                  → New message sending endpoint
/api/get_messages.php                  → New message fetching endpoint

/public/assets/css/messenger.css       → Messenger-inspired styling
/public/assets/js/messenger.js         → Message functionality

/sql/migration_messages.sql            → Database migration file
```

---

## 🔧 Technical Details

### Admin Messages Page (`/admin/messages.php`)

**Features:**
- Two-panel layout (conversations + messages)
- Search conversations in real-time
- Message polling (auto-refresh)
- Unread message badges
- Auto-scroll to latest message
- Responsive design

**URL:** `admin/messages.php?tenant={id}`

---

### Tenant Chat Modal (`/tenant/profile.php`)

**Integration:**
- Added to bottom of profile page
- Floating button with gradient background
- Professional modal dialog
- Email field is optional
- Form validation

**API Endpoint:** `POST /api/send_message.php`

---

### API Endpoints

#### **POST `/api/send_message.php`**

Sends a message from tenant or admin reply.

**Parameters:**
```php
receiver_id        (int, required) - User ID of message recipient
message           (string, required) - Message content
sender_email      (string, optional) - Email for tenant messages
from_admin        (int, optional) - Flag for admin message
from_tenant       (int, optional) - Flag for tenant message
```

**Response:**
```json
{
  "success": true,
  "message_id": 123,
  "message": "Message sent successfully"
}
```

**Email Notifications:**
- If tenant provides email → Admin receives notification
- If admin replies → Tenant receives notification + email (if enabled)

---

#### **GET `/api/get_messages.php`**

Fetches messages in a conversation.

**Parameters:**
```php
peer    (int, required) - User ID to fetch messages with
limit   (int, optional) - Message limit (default: 50)
```

**Response:**
```json
{
  "success": true,
  "messages": [
    {
      "id": 1,
      "sender_id": 5,
      "receiver_id": 1,
      "message": "Hello admin",
      "created_at": "2026-02-19 10:30:00",
      "first_name": "John",
      "last_name": "Doe"
    }
  ],
  "count": 1
}
```

---

## 🎨 CSS & Styling

### Messenger CSS (`/public/assets/css/messenger.css`)

**Design Inspired By:** Facebook Messenger

**Key Styles:**
- Modern two-panel layout
- Smooth transitions and hover effects
- Color-coded messages (sent/received)
- Unread badges
- Responsive typography
- Mobile-friendly design

**Color Scheme:**
```css
--primary-blue: #667eea
--primary-dark: #764ba2
--sent-bg: #667eea
--received-bg: #e5e5ea
```

---

## 📱 JavaScript Functionality

### Messenger JS (`/public/assets/js/messenger.js`)

**Features:**
- Auto-scroll to latest message
- Real-time message polling (3-second intervals)
- Conversation search
- Textarea auto-expansion
- Send on Ctrl+Enter / Cmd+Enter
- Unread message indicators
- Responsive interactions

---

## 🚀 Installation & Setup

### Step 1: Run Database Migration
```sql
-- Execute migration file in MySQL
source sql/migration_messages.sql;
```

### Step 2: Verify Files Created
- ✅ `/admin/messages.php`
- ✅ `/api/send_message.php`
- ✅ `/api/get_messages.php`
- ✅ `/public/assets/css/messenger.css`
- ✅ `/public/assets/js/messenger.js`

### Step 3: Update Navigation
Admin navigation now includes:
- Dashboard
- Tenants
- Payments
- Reports
- Stalls
- **Messages** ← NEW
- Notifications
- Account
- Contact

### Step 4: Test the System

**For Tenants:**
1. Go to Profile → Scroll to bottom
2. Click "Chat with Admin" button
3. Enter optional email
4. Send test message

**For Admins:**
1. Go to Admin Dashboard
2. Click "Messages" in navigation
3. Check if tenant message appears
4. Reply to message
5. Verify notification sent to tenant

---

## ✉️ Email Integration

### Email Notifications Sent When:

1. **Tenant Sends Message with Email:**
   - Email sent to admin about new tenant message
   - Includes tenant email for direct reply option

2. **Admin Replies:**
   - Email sent to tenant (if notifications enabled)
   - Includes message content
   - Link to view in notifications

### Email Template Variables:
```php
{senderName}      - Full name of message sender
{receiverName}    - Full name of message receiver
{message}         - Message content
{tenantEmail}     - Tenant's provided email
{timestamp}       - Message timestamp
```

---

## 🔒 Security Features

- ✅ Session validation (user must be logged in)
- ✅ Role-based access control
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (htmlspecialchars, htmlentities)
- ✅ Message ownership verification

---

## 🔄 Migration from Old System

### Old Chat System:
- Chat embedded in Admin Notifications
- Messages stored in notifications table
- Basic UI

### New Chat System:
- Dedicated Messages page for admin
- New messages table for better organization
- Modern messenger interface
- Email integration

### Backward Compatibility:
- Old notifications table still functional
- Can coexist with new system
- Gradual migration possible

---

## 📊 Unread Message Management

**Automatic:**
- New messages marked as unread
- Unread count shown in conversation list
- Badge displays count

**Manual Mark as Read:**
- Automatic when conversation opened
- Via database field `is_read`

---

## 🐛 Troubleshooting

### Messages Not Sending:
1. Check admin user exists in database
2. Verify sender_id and receiver_id are valid
3. Check API endpoint response in console

### Emails Not Sending:
1. Verify SMTP settings in `/config/mailer.php`
2. Check if `notify_email_on_messages` is enabled for user
3. Verify email address is valid
4. Check server error logs

### Conversation Not Loading:
1. Check conversation exists in database
2. Verify user permissions
3. Clear browser cache

### CSS/JS Not Loading:
1. Verify file paths are correct
2. Check file permissions
3. Clear browser cache and hard refresh (Ctrl+Shift+R)

---

## 📋 Database Queries Reference

### Get Unread Message Count:
```sql
SELECT COUNT(*) FROM messages 
WHERE receiver_id = ? AND is_read = 0;
```

### Get Conversation List:
```sql
SELECT DISTINCT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name
FROM users u
JOIN messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
WHERE (m.sender_id = ? OR m.receiver_id = ?)
GROUP BY u.id;
```

### Get Latest Messages:
```sql
SELECT * FROM messages 
WHERE (sender_id = ? AND receiver_id = ?)
   OR (sender_id = ? AND receiver_id = ?)
ORDER BY created_at DESC LIMIT 50;
```

---

## 🎓 Best Practices

1. **For Users:**
   - Save important messages
   - Check notifications regularly
   - Provide email for important communications

2. **For Admins:**
   - Reply promptly to tenant messages
   - Mark resolved conversations if needed
   - Archive old conversations

3. **For Developers:**
   - Keep messages table indexed
   - Regular database backups
   - Monitor email delivery
   - Log all API calls

---

## 📞 Support & Maintenance

### Regular Maintenance Tasks:
- Monitor message table size
- Archive old messages periodically
- Review email delivery logs
- Update email templates

### Developer Notes:
- All API endpoints return JSON
- AJAX calls use FormData for compatibility
- Timestamps in UTC (MySQL)
- User input validated on both client and server

---

## Version Information

- **Version:** 1.0
- **Updated:** February 19, 2026
- **Compatibility:** PHP 7.4+, MySQL 5.7+
- **Dependencies:** PHPMailer (for email), PDO (database)

---

**End of Documentation**
