# RentFlow Quick Reference Guide

## 🚀 Quick Links

### CSS Reference
- **Main CSS:** `/rentflow/public/assets/css/bootstrap-custom.css`
- **Tenant CSS:** `/rentflow/public/assets/css/tenant-bootstrap.css`

### JS Reference
- **Modal Manager:** `/rentflow/public/assets/js/modal-manager.js`
- **Charts:** `/rentflow/public/assets/js/charts.js`
- **UI:** `/rentflow/public/assets/js/ui.js`

---

## 📋 Common Tasks

### How to Add a Modal to a Page

```html
<!-- 1. Create the modal HTML -->
<div id="myModal" class="modal">
  <div class="modal-content">
    <button class="modal-close">&times;</button>
    <h2>Modal Title</h2>
    <form>
      <!-- Your form here -->
    </form>
  </div>
</div>

<!-- 2. Add a trigger button -->
<button onclick="openModal('myModal')">Open Modal</button>

<!-- 3. Include the modal manager JS -->
<script src="/rentflow/public/assets/js/modal-manager.js"></script>
```

### How to Use Data Attributes

```html
<!-- Automatic trigger -->
<button data-modal-trigger="myModal">Open</button>

<!-- Automatic close -->
<button data-modal-close="myModal">Close</button>
```

### How to Show an Alert

```javascript
showAlert('Success message', 'success', 5000);
showAlert('Error message', 'danger', 0); // No auto-close
showAlert('Warning', 'warning', 3000);
```

### How to Open Image Viewer

```javascript
openImageModal('/path/to/image.jpg', 'Stall A-123');
```

### How to Format Currency

```javascript
const formatted = formatPeso(1500.50); // ₱1,500.50
```

### How to Format Date

```javascript
formatDate('2024-02-03', 'short');  // Feb 03, 2024
formatDate('2024-02-03', 'long');   // February 03, 2024
formatDate('2024-02-03', 'full');   // Friday, February 03, 2024
```

---

## 🎨 CSS Variables

All CSS variables are defined in `:root`:

```css
--primary: #0B3C5D;           /* Main brand color */
--primary-dark: #082a42;      /* Darker shade */
--primary-light: #e6f2f8;     /* Light background */
--golden: #F2B705;             /* Accent color */
--secondary: #6B7280;          /* Text secondary */
--success: #1F7A1F;            /* Success state */
--danger: #8B1E1E;             /* Error state */
--warning: #F2B705;            /* Warning state */
--light: #f8f9fa;              /* Light background */
--white: #fff;                 /* White */
--dark: #050505;               /* Dark text */
--border: #ddd;                /* Border color */
--shadow-sm: ...;              /* Small shadow */
--shadow-md: ...;              /* Medium shadow */
--shadow-lg: ...;              /* Large shadow */
```

**Usage:**
```css
color: var(--primary);
background: var(--light);
border: 1px solid var(--border);
```

---

## 📱 Responsive Breakpoints

```css
/* Mobile First */
/* Base styles apply to 0px+ */

/* Tablet */
@media (max-width: 768px) { ... }

/* Large screens */
@media (max-width: 1024px) { ... }

/* Mobile devices */
@media (max-width: 480px) { ... }
```

---

## 🔧 Modal Manager API

### Open Modal
```javascript
openModal('modalId');           // By ID
openModal(modalElement);        // By element
```

### Close Modal
```javascript
closeModal('modalId');          // By ID
closeModal(modalElement);       // By element
```

### Toggle Modal
```javascript
toggleModal('modalId');         // By ID
toggleModal(modalElement);      // By element
```

### Apply Modal (Pre-fills)
```javascript
openApplyModal('A-123', 'Food', 'applyModal');
// stallNo, type, modalId
```

### Image Modal
```javascript
openImageModal('/path/to/image.jpg', 'Title');
```

### Alert
```javascript
showAlert(message, type, duration);
// type: 'success', 'danger', 'warning', 'info'
// duration: milliseconds (0 = no auto-close)
```

---

## 🎯 Form Handling

### Reset Form
```javascript
resetForm('formId');
// or
resetForms(); // All forms on page
```

### Disable Form Temporarily
```javascript
disableFormSubmit('formId', 3000); // 3 seconds
```

---

## 🚨 Event Listeners

Modal manager automatically adds listeners for:

1. **Click outside modal** → Closes modal
2. **Escape key** → Closes modal
3. **X button** → Closes modal
4. **Data attributes** → Auto-trigger/close buttons

---

## 📦 Class Reference

### Button Classes
```html
<button class="btn">Default</button>
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-success">Success</button>
<button class="btn btn-danger">Danger</button>
<button class="btn btn-small">Small</button>
```

### Alert Classes
```html
<div class="alert alert-success">Success message</div>
<div class="alert alert-danger">Error message</div>
<div class="alert alert-warning">Warning message</div>
<div class="alert alert-info">Info message</div>
```

### Card Classes
```html
<div class="card">
  <img class="card-image" src="...">
  <div class="card-body">
    <h3 class="card-title">Title</h3>
    <p class="card-text">Description</p>
    <div class="card-footer">
      <button class="btn btn-primary">Action</button>
    </div>
  </div>
</div>
```

### Form Classes
```html
<div class="form-group">
  <label>Label</label>
  <input type="text">
  <small>Helper text</small>
</div>
```

### Table Classes
```html
<div class="table-responsive">
  <table class="table">
    <thead>...</thead>
    <tbody>...</tbody>
  </table>
</div>
```

---

## 🔍 Debugging Tips

### Check Modal Display
```javascript
// Check if modal exists
console.log(document.getElementById('myModal'));

// Check if modal is shown
console.log(document.getElementById('myModal').classList.contains('show'));
```

### Check Form Data
```javascript
const form = document.getElementById('formId');
const data = new FormData(form);
for (let [key, value] of data) {
  console.log(key, value);
}
```

### Test Responsive
Use browser DevTools:
- Ctrl+Shift+M (Windows/Linux)
- Cmd+Shift+M (Mac)

---

## ⚡ Performance Tips

1. **CSS Variables** use computed values (cached)
2. **Modal animations** are GPU-accelerated
3. **Event delegation** reduces memory footprint
4. **Responsive images** load based on viewport

---

## 🚀 Deployment Checklist

- [ ] Test all modals on mobile (480px)
- [ ] Test all modals on tablet (768px)
- [ ] Test all modals on desktop (1024px+)
- [ ] Verify form submissions work
- [ ] Check console for JavaScript errors
- [ ] Test accessibility (keyboard navigation)
- [ ] Verify all links work
- [ ] Check loading times

---

## 📚 Files to Never Edit

⚠️ These are auto-generated or critical:
- `/vendor/` - Composer dependencies
- `composer.json` - Package list
- `sql/` - Database schemas

---

## 💡 Best Practices

1. **Always use modal-manager.js** for modals
2. **Keep forms inside modals** for validation
3. **Use CSS variables** for colors
4. **Test responsiveness** before pushing
5. **Validate form inputs** server-side
6. **Never disable escape key** for modals
7. **Always reset forms** on modal close

---

## 🆘 Common Issues & Fixes

### Modal not closing
```javascript
// Check if modal exists
const modal = document.getElementById('modalId');
if (!modal) console.error('Modal not found');

// Try manual close
closeModal('modalId');
```

### Form not submitting
```javascript
// Check form action attribute
<form action="/rentflow/api/endpoint.php" method="post">

// Check for validation errors
form.addEventListener('submit', (e) => {
  if (!form.checkValidity()) e.preventDefault();
});
```

### Styling not applying
```css
/* Check CSS specificity */
/* Use !important only as last resort */
color: var(--primary) !important;

/* Clear browser cache */
/* Ctrl+Shift+Delete */
```

### Image not displaying
```html
<!-- Check path is correct -->
<img src="/rentflow/public/assets/images/image.jpg">

<!-- Use absolute paths in PHP -->
<?php echo htmlspecialchars($imagePath); ?>
```

---

## 📞 Support

For issues or questions:
1. Check IMPLEMENTATION_SUMMARY.md
2. Check VERIFICATION_CHECKLIST.md
3. Review modal-manager.js documentation
4. Check bootstrap-custom.css variables

---

**Last Updated:** February 3, 2026
**Version:** 1.0.0
