# Style & Script Audit Report

## Overview
Audit of all PHP/HTML files to identify inline `<style>` and `<script>` tags that should be moved to external files.

---

## Files with Inline Styles Found

### 1. **public/login.php**
- **Location**: Lines 223-293
- **Status**: ✅ Already uses external CSS (`bootstrap-custom.css`)
- **Inline Styles**: YES - Large `<style>` block with:
  - `.card-container`, `.btn`, `.alert`, `.info-box`, `.footer` styles
  - Media queries for responsive design
- **Action Required**: Extract to `public/assets/css/login-page.css`

### 2. **public/register.php**
- **Locations**: 
  - Lines 95-112 (Email OTP styling in PHP string)
  - Lines 224-320 (Main page styles)
  - Lines 474-495 (Modal styles)
- **Status**: ✅ Uses `bootstrap-custom.css`
- **Inline Styles**: YES - Multiple `<style>` blocks
- **Action Required**: Extract to `public/assets/css/register-page.css`

### 3. **public/terms_accept.php**
- **Location**: Lines 115-150
- **Status**: ✅ Uses `layout.css`, `auth-common.css`, `signup.css`
- **Inline Styles**: YES - `.policies-container`, `.policies-content` styles
- **Action Required**: Extract to `public/assets/css/terms-page.css`

### 4. **public/forgot_password.php**
- **Location**: Line 110
- **Status**: ✅ Uses `layout.css`, `auth-common.css`, `login.css`
- **Inline Styles**: YES - `.success` class styling
- **Action Required**: Extract to `public/assets/css/forgot-password.css`

### 5. **public/reset_password.php**
- **Location**: Lines 101-191
- **Status**: ✅ Uses `layout.css`, `auth-common.css`, `login.css`
- **Inline Styles**: YES - Large block with `.modal`, `.modal-content`, `.otp-input`, etc.
- **Action Required**: Extract to `public/assets/css/reset-password.css`

### 6. **public/verify_2fa.php**
- **Locations**: Already uses external `verify_2fa.css` ✅
- **Status**: GOOD - All styles in external file

### 7. **tenant/stalls.php**
- **Location**: Lines 255+ (ending script)
- **Status**: Uses external CSS files
- **Inline Scripts**: YES - JavaScript for modal management
- **Action Required**: Extract to `public/assets/js/stalls-page.js`

### 8. **chat/chat.php**
- **Location**: Lines 36+ (inline script for chat polling)
- **Status**: Uses external `notification.js`
- **Inline Scripts**: YES - Chat polling and helper functions
- **Action Required**: Extract to `public/assets/js/chat-page.js`

### 9. **admin/dashboard.php**
- **Status**: Uses `layout.css` only
- **Inline Styles**: NONE ✅

### 10. **admin/login.php**
- **Status**: Uses `layout.css`, `auth-common.css`, `login.css`
- **Inline Styles**: NONE ✅

### 11. **treasury/login.php**
- **Status**: Uses multiple CSS files
- **Inline Styles**: NONE ✅

---

## Files with Inline Scripts Found

### 1. **public/register.php**
- **Locations**: Lines 505-572
- **Content**: OTP verification form handling, modal management
- **Status**: Needs extraction
- **Action Required**: Extract to `public/assets/js/register-page.js`

### 2. **public/reset_password.php**
- **Locations**: Lines 193-375
- **Content**: Password reset form handling, modal management
- **Status**: Needs extraction
- **Action Required**: Extract to `public/assets/js/reset-password.js`

### 3. **public/terms_accept.php**
- **Locations**: Lines 255-302
- **Content**: Checkbox event listeners for terms and 2FA options
- **Status**: Needs extraction
- **Action Required**: Extract to `public/assets/js/terms-page.js`

### 4. **tenant/stalls.php**
- **Locations**: Lines 255+
- **Content**: Modal management for stall applications
- **Status**: Needs extraction
- **Action Required**: Extract to `public/assets/js/stalls-page.js`

### 5. **chat/chat.php**
- **Locations**: Lines 36+
- **Content**: Chat polling and message display
- **Status**: Needs extraction
- **Action Required**: Extract to `public/assets/js/chat-page.js`

---

## Email Template Styles (Inside PHP)

### Files with Email Template Styles:
1. **public/login.php** - Lines 95-112 (OTP email styles)
2. **public/register.php** - Lines 95-112, 265-285 (OTP email styles)
3. **public/forgot_password.php** - Lines 43-61 (OTP email styles)

**Note**: These are embedded in email content, so they should remain inline (email clients don't load external CSS). No action needed.

---

## Summary Table

| File | Type | Current Status | Lines | Action |
|------|------|---|---|---|
| public/login.php | Styles | Inline | 223-293 | Extract |
| public/register.php | Styles | Inline | 224-320, 474-495 | Extract |
| public/register.php | Scripts | Inline | 505-572 | Extract |
| public/terms_accept.php | Styles | Inline | 115-150 | Extract |
| public/terms_accept.php | Scripts | Inline | 255-302 | Extract |
| public/forgot_password.php | Styles | Inline | 110 | Extract |
| public/reset_password.php | Styles | Inline | 101-191 | Extract |
| public/reset_password.php | Scripts | Inline | 193-375 | Extract |
| public/verify_2fa.php | - | External CSS | ✅ | None |
| tenant/stalls.php | Scripts | Inline | 255+ | Extract |
| chat/chat.php | Scripts | Inline | 36+ | Extract |
| admin/dashboard.php | - | External CSS | ✅ | None |
| admin/login.php | - | External CSS | ✅ | None |
| treasury/login.php | - | External CSS | ✅ | None |

---

## Recommended Actions

### Phase 1: Extract CSS Files
1. `public/assets/css/login-page.css` - from public/login.php
2. `public/assets/css/register-page.css` - from public/register.php
3. `public/assets/css/terms-page.css` - from public/terms_accept.php
4. `public/assets/css/forgot-password-page.css` - from public/forgot_password.php
5. `public/assets/css/reset-password-page.css` - from public/reset_password.php

### Phase 2: Extract JS Files
1. `public/assets/js/register-page.js` - from public/register.php
2. `public/assets/js/reset-password-page.js` - from public/reset_password.php
3. `public/assets/js/terms-page.js` - from public/terms_accept.php
4. `public/assets/js/stalls-page.js` - from tenant/stalls.php
5. `public/assets/js/chat-page.js` - from chat/chat.php

### Phase 3: Update HTML References
- Replace inline `<style>` tags with `<link rel="stylesheet" href="...">`
- Replace inline `<script>` tags with `<script src="..."></script>`
- Update all relative paths to absolute paths starting with `/rentflow/`

---

## Current Asset Structure
```
public/assets/
├── css/
│   ├── auth-common.css ✅
│   ├── bootstrap-custom.css ✅
│   ├── layout.css ✅
│   ├── login.css ✅
│   ├── signup.css ✅
│   ├── tenant-bootstrap.css ✅
│   ├── tenant-sidebar.css ✅
│   └── verify_2fa.css ✅
└── js/
    ├── charts.js ✅
    ├── modal-manager.js ✅
    ├── notifications.js ✅
    ├── table.js ✅
    ├── ui.js ✅
    └── verify_2fa.js ✅
```

---

## Notes
- Email template styles are intentionally kept inline (email client compatibility)
- Inline styles in HTML attributes (e.g., `style="..."`) on elements are acceptable for minimal styling
- Focus on removing large `<style>` blocks and `<script>` blocks from HTML

