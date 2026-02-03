# Admin CSS Quick Reference

## CSS File Location
`/public/assets/css/admin.css`

## HTML Head Setup for Admin Pages
```html
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Your Page Title - RentFlow</title>
  <link rel="stylesheet" href="/rentflow/public/assets/css/admin.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
```

## Common HTML Elements & Classes

### Header & Navigation
```html
<header class="header">
  <h1 class="site-title">RentFlow</h1>
  <nav class="navigation">
    <ul>
      <li><a href="#">Link</a></li>
      <li><a href="#" class="active">Active Link</a></li>
    </ul>
  </nav>
</header>
```

### Main Content
```html
<main class="content">
  <h1>Page Title</h1>
  <section class="table-section">
    <!-- content -->
  </section>
</main>
```

### Tables
```html
<table class="table">
  <thead>
    <tr>
      <th>Header 1</th>
      <th>Header 2</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Data 1</td>
      <td>Data 2</td>
    </tr>
  </tbody>
</table>
```

### Buttons
```html
<!-- Primary Button (Default) -->
<button class="btn">Click Me</button>

<!-- Danger Button -->
<button class="btn danger">Delete</button>

<!-- Success Button -->
<button class="btn success">Save</button>

<!-- Warning Button -->
<button class="btn warning">Warning</button>

<!-- Secondary Button -->
<button class="btn secondary">Secondary</button>

<!-- Light Button -->
<button class="btn light">Light</button>

<!-- Small Button -->
<button class="btn small">Small</button>
```

### Badges / Status Indicators
```html
<span class="badge Paid">Paid</span>
<span class="badge Pending">Pending</span>
<span class="badge Overdue">Overdue</span>
<span class="badge active">Active</span>
<span class="badge inactive">Inactive</span>
<span class="badge available">Available</span>
<span class="badge maintenance">Maintenance</span>
```

### Forms
```html
<form>
  <div class="form-group">
    <label>Email</label>
    <input type="email" name="email" required>
  </div>

  <div class="form-group">
    <label>Status</label>
    <select name="status">
      <option>Select...</option>
      <option>Active</option>
      <option>Inactive</option>
    </select>
  </div>

  <div class="form-group">
    <label>Notes</label>
    <textarea name="notes"></textarea>
  </div>

  <button type="submit" class="btn">Submit</button>
</form>
```

### Cards
```html
<div class="card">
  <h3>Card Title</h3>
  <div class="card-value">1,234</div>
  <div class="card-label">Total Items</div>
</div>
```

### Grid Layout
```html
<!-- Auto-responsive grid -->
<div class="grid">
  <div class="card">Card 1</div>
  <div class="card">Card 2</div>
  <div class="card">Card 3</div>
</div>

<!-- Fixed grid-2 -->
<div class="grid-2">
  <div>Item 1</div>
  <div>Item 2</div>
</div>

<!-- Fixed grid-3 -->
<div class="grid-3">
  <div>Item 1</div>
  <div>Item 2</div>
  <div>Item 3</div>
</div>
```

### Alerts
```html
<div class="alert success">Success message here</div>
<div class="alert danger">Error message here</div>
<div class="alert warning">Warning message here</div>
<div class="alert info">Info message here</div>
```

### Sections
```html
<section class="section">
  <h2>Section Title</h2>
  <!-- content -->
</section>

<section class="table-section">
  <h2>Table Title</h2>
  <table class="table">
    <!-- table content -->
  </table>
</section>

<section class="form-section">
  <h2>Form Title</h2>
  <form>
    <!-- form content -->
  </form>
</section>
```

## Utility Classes

### Text Alignment
- `.text-center` - Center text
- `.text-right` - Right align
- `.text-left` - Left align

### Text Colors
- `.text-primary` - Primary blue
- `.text-success` - Green
- `.text-danger` - Red
- `.text-warning` - Orange
- `.text-info` - Light blue
- `.text-muted` - Gray

### Spacing (Margins)
- `.mt-0`, `.mt-1`, `.mt-2`, `.mt-3`, `.mt-4` - Top margin
- `.mb-0`, `.mb-1`, `.mb-2`, `.mb-3`, `.mb-4` - Bottom margin

### Spacing (Padding)
- `.p-0`, `.p-1`, `.p-2`, `.p-3`, `.p-4` - Padding on all sides

### Flexbox
- `.flex` - Display flex
- `.flex-center` - Center items
- `.flex-between` - Space between items
- `.flex-column` - Vertical layout
- `.gap-1`, `.gap-2`, `.gap-3`, `.gap-4` - Gap between items

### Sizing
- `.w-full` - Width 100%
- `.h-full` - Height 100%

## Color Variables (Customizable)
```css
--primary: #0B3C5D
--primary-dark: #082a42
--primary-light: #e6f2f8
--golden: #F2B705
--success: #1F7A1F
--danger: #8B1E1E
--warning: #F2B705
--info: #3498db
--secondary: #6B7280
--text: #050505
--white: #fff
--border: #ddd
```

## Responsive Breakpoints

CSS automatically adapts to these screen sizes:

- **1000px+**: Full desktop layout
- **768-999px**: Tablet/medium layout
- **480-767px**: Mobile layout
- **<480px**: Extra small mobile

No need to add media queries - the CSS handles it automatically!

## Accessibility Notes
- ✅ Buttons have minimum 36px clickable area
- ✅ Form inputs have clear focus states
- ✅ Color contrast meets WCAG standards
- ✅ Touch-friendly on mobile devices
- ✅ Keyboard navigation supported

## Performance
- Single CSS file: Only 1 HTTP request
- ~27 KB uncompressed
- ~7 KB gzipped
- No render-blocking resources
- Optimized animations and transitions

## Examples

### Admin Dashboard Card
```html
<div class="grid-3">
  <div class="card">
    <div class="card-label">Total Revenue</div>
    <div class="card-value">₱125,000</div>
  </div>
  <div class="card">
    <div class="card-label">Pending Payments</div>
    <div class="card-value">15</div>
  </div>
  <div class="card">
    <div class="card-label">Active Tenants</div>
    <div class="card-value">42</div>
  </div>
</div>
```

### Payment Status Table
```html
<table class="table">
  <thead>
    <tr>
      <th>Stall</th>
      <th>Tenant</th>
      <th>Amount</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>A-101</td>
      <td>John Doe</td>
      <td>₱5,000</td>
      <td><span class="badge Paid">Paid</span></td>
      <td><button class="btn small">Edit</button></td>
    </tr>
  </tbody>
</table>
```

### Form with Validation
```html
<form method="POST">
  <div class="form-group">
    <label>Business Name</label>
    <input type="text" name="business_name" required>
  </div>

  <div class="form-group">
    <label>Email Address</label>
    <input type="email" name="email" required>
  </div>

  <div class="form-group">
    <label>Status</label>
    <select name="status" required>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
  </div>

  <div class="flex gap-2">
    <button type="submit" class="btn success">Save Changes</button>
    <button type="reset" class="btn light">Cancel</button>
  </div>
</form>
```

---
For detailed information, see: `ADMIN_CSS_SEPARATION.md`
