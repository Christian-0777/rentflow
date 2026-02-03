# RentFlow JavaScript API - Quick Reference

## Namespace Structure

All functionality is organized under the `window.RentFlow` namespace:

```javascript
RentFlow.modal       // Modal management
RentFlow.ui         // UI utilities and alerts
RentFlow.table      // Table operations
RentFlow.chart      // Chart rendering
RentFlow.notifications // Notification system
```

---

## Modal Management - RentFlow.modal

### Open Modal
```javascript
RentFlow.modal.open('modalId');
RentFlow.modal.open(element);  // Can also pass element
```

### Close Modal
```javascript
RentFlow.modal.close('modalId');
RentFlow.modal.close(element);
```

### Toggle Modal
```javascript
RentFlow.modal.toggle('modalId');
```

### Open Image Modal
```javascript
RentFlow.modal.openImageModal('image-path.jpg', 'Image Title');
```

### Data Attributes (Automatic)
```html
<!-- Automatically open modal on click -->
<button data-modal-trigger="modalId">Open</button>

<!-- Automatically close modal on click -->
<button data-modal-close="modalId">Close</button>
<button data-modal-close="parent">Close Parent Modal</button>

<!-- Modal X button -->
<button class="modal-close">&times;</button>
```

---

## UI Utilities - RentFlow.ui

### Show Alert
```javascript
RentFlow.ui.showAlert('Success!', 'success');        // 5s auto-close
RentFlow.ui.showAlert('Error!', 'danger', 10000);    // 10s auto-close
RentFlow.ui.showAlert('Info', 'info', 0);            // No auto-close

// Types: success, danger, warning, info
```

### Close Alert
```javascript
const alertId = RentFlow.ui.showAlert('Message');
RentFlow.ui.closeAlert(alertId);
```

### Format Currency
```javascript
RentFlow.ui.formatPeso(1000);      // Returns: ₱1,000.00
RentFlow.ui.formatPeso(99.5);      // Returns: ₱99.50
```

### Format Date
```javascript
RentFlow.ui.formatDate('2024-02-03');           // Returns: Feb 03, 2024
RentFlow.ui.formatDate('2024-02-03', 'long');   // Returns: February 03, 2024
RentFlow.ui.formatDate('2024-02-03', 'full');   // Returns: Friday, February 03, 2024
```

### Confirmation Dialog
```javascript
RentFlow.ui.showConfirm(
  'Are you sure?',
  function() { console.log('Confirmed'); },
  function() { console.log('Cancelled'); }
);
```

### Device Detection
```javascript
RentFlow.ui.isMobileDevice();      // Returns: true/false
RentFlow.ui.isSmallScreen();       // Returns: true/false (width <= 768px)
RentFlow.ui.getCurrentBreakpoint(); // Returns: 'xs', 'sm', 'md', or 'lg'
```

### HTML Escaping
```javascript
RentFlow.ui.escapeHtml('<script>alert("xss")</script>');
// Returns safe escaped HTML
```

---

## Table Operations - RentFlow.table

### Initialize Table Sorting
```javascript
RentFlow.table.init();  // Initializes all tables with class="table"
```

### Sort Specific Table
```javascript
RentFlow.table.sortTable(tableElement, columnIndex);
```

### Export to CSV
```javascript
RentFlow.table.exportToCSV('tableId');                    // export.csv
RentFlow.table.exportToCSV('tableId', 'my-data.csv');     // custom filename
```

### HTML Example
```html
<table class="table">
  <thead>
    <tr>
      <th>Name</th>        <!-- Click to sort -->
      <th>Amount</th>      <!-- Click to sort -->
      <th>Date</th>        <!-- Click to sort -->
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>John</td>
      <td>1000</td>
      <td>2024-02-03</td>
    </tr>
  </tbody>
</table>
```

---

## Chart Rendering - RentFlow.chart

### Create Generic Chart
```javascript
RentFlow.chart.create('canvasId', 'pie', {
  labels: ['Label 1', 'Label 2'],
  datasets: [
    { label: 'Series 1', data: [10, 20], backgroundColor: '#0B3C5D' }
  ],
  options: { /* Chart.js options */ }
});
```

### Pie Chart
```javascript
RentFlow.chart.pie('chartId', 
  ['Available', 'Occupied'],
  [
    { label: 'Stalls', data: [5, 10], color: '#0B3C5D' },
    { label: 'Spaces', data: [3, 7], color: '#F2B705' }
  ]
);
```

### Bar Chart
```javascript
RentFlow.chart.bar('chartId',
  ['Jan', 'Feb', 'Mar'],
  [100, 150, 200],
  'Revenue'
);
```

### Line Chart
```javascript
RentFlow.chart.line('chartId',
  ['Week 1', 'Week 2', 'Week 3'],
  [50, 75, 100],
  'Growth'
);
```

### Doughnut Chart
```javascript
RentFlow.chart.doughnut('chartId',
  ['Category A', 'Category B'],
  [
    { label: 'Data', data: [30, 70], color: '#0B3C5D' }
  ]
);
```

### Export Chart
```javascript
RentFlow.chart.exportPNG('chartId');                // chart.png
RentFlow.chart.exportPNG('chartId', 'report.png');  // custom filename

RentFlow.chart.exportPDF('chartId');                // chart.pdf (via API)
RentFlow.chart.exportPDF('chartId', 'report.pdf');  // custom filename
```

---

## Notifications - RentFlow.notifications

### Poll Notifications (One-time)
```javascript
RentFlow.notifications.poll('notificationListId');
RentFlow.notifications.poll('notificationListId', 20);  // Limit to 20
```

### Poll Notifications (Periodic)
```javascript
RentFlow.notifications.poll('notificationListId', 10, 5000);
// Fetch 10 notifications every 5 seconds
```

### Manual Fetch
```javascript
RentFlow.notifications.fetch('notificationListId', 10);
```

### HTML Example
```html
<ul id="notification-list">
  <!-- Notifications will be populated here -->
</ul>

<script>
  RentFlow.notifications.poll('notification-list', 10, 30000);
  // Fetch up to 10 notifications every 30 seconds
</script>
```

---

## Backward Compatibility - Legacy Functions

All old functions still work! They're automatically mapped:

```javascript
openModal('id')                    → RentFlow.modal.open('id')
closeModal('id')                   → RentFlow.modal.close('id')
toggleModal('id')                  → RentFlow.modal.toggle('id')
openImageModal(path, title)        → RentFlow.modal.openImageModal(path, title)
showAlert(msg, type, duration)     → RentFlow.ui.showAlert(msg, type, duration)
closeAlert(id)                     → RentFlow.ui.closeAlert(id)
formatPeso(amount)                 → RentFlow.ui.formatPeso(amount)
formatDate(date, format)           → RentFlow.ui.formatDate(date, format)
showConfirm(msg, onConfirm, onCancel) → RentFlow.ui.showConfirm(...)
exportTableToCSV(tableId, filename) → RentFlow.table.exportToCSV(...)
renderChart(...)                   → RentFlow.chart.create(...)
renderPie(...)                     → RentFlow.chart.pie(...)
renderBar(...)                     → RentFlow.chart.bar(...)
renderLine(...)                    → RentFlow.chart.line(...)
exportPNG(...)                     → RentFlow.chart.exportPNG(...)
exportPDF(...)                     → RentFlow.chart.exportPDF(...)
pollNotifications(...)             → RentFlow.notifications.poll(...)
```

---

## CSS Design System - Variables

All colors, spacing, and typography are in `base.css`:

### Colors
```css
--primary: #0B3C5D              /* Main brand color */
--primary-dark: #082a42         /* Darker variant */
--primary-light: #e6f2f8        /* Lighter variant */
--golden: #F2B705               /* Accent color */
--success: #1F7A1F              /* Success green */
--danger: #8B1E1E               /* Danger red */
--warning: #F2B705              /* Warning yellow */
--text: #050505                 /* Main text color */
--border: #ddd                  /* Border color */
```

### Spacing Scale
```css
--spacing-xs: 4px
--spacing-sm: 8px
--spacing-md: 12px
--spacing-lg: 16px
--spacing-xl: 24px
--spacing-2xl: 32px
--spacing-3xl: 48px
```

### Usage
```css
.my-element {
  color: var(--primary);
  padding: var(--spacing-lg);
  margin-bottom: var(--spacing-xl);
  box-shadow: var(--shadow-md);
}
```

---

## Common Patterns

### Open Modal on Button Click
```html
<button data-modal-trigger="myModal">Open</button>

<div id="myModal" class="modal">
  <div class="modal-content">
    <button class="modal-close">&times;</button>
    <h2>Modal Title</h2>
    <p>Modal content here</p>
  </div>
</div>
```

### Show Success Message
```javascript
RentFlow.ui.showAlert('Payment received successfully!', 'success');
```

### Create and Export Chart
```javascript
RentFlow.chart.pie('revenueChart', 
  ['Available', 'Occupied'],
  [{ label: 'Stalls', data: [5, 10], color: '#0B3C5D' }]
);

// Later, user clicks export
document.getElementById('exportBtn').onclick = function() {
  RentFlow.chart.exportPNG('revenueChart', 'revenue-chart.png');
};
```

### Handle Errors in Notifications
```javascript
try {
  RentFlow.notifications.poll('notif-list', 10);
} catch(e) {
  RentFlow.ui.showAlert('Failed to load notifications', 'danger');
  console.error('Notification error:', e);
}
```

---

## Performance Tips

1. **Call `RentFlow.table.init()` once** after table HTML is loaded
2. **Cache frequently accessed elements:**
   ```javascript
   const modal = document.getElementById('myModal');
   RentFlow.modal.open(modal);  // Faster than ID lookup
   ```

3. **For auto-polling, use reasonable intervals:**
   ```javascript
   RentFlow.notifications.poll('notif-list', 10, 30000);  // 30s interval
   // NOT: RentFlow.notifications.poll('notif-list', 10, 1000);  // Too frequent!
   ```

4. **Reuse chart instances** instead of recreating:
   ```javascript
   // Don't do this:
   RentFlow.chart.pie(...);  // Every render
   
   // Do this:
   const chart = RentFlow.chart.pie(...);
   // Update data and call chart.update() if needed
   ```

---

## Debugging

All modules log errors to console:

```javascript
// Check browser console for error messages
// Look for 'Chart:', 'Notifications:', 'Modal:', 'UI:' prefixes
```

Enable verbose logging:
```javascript
console.log(RentFlow);  // View entire API structure
console.log(RentFlow.chart.create);  // View function
```

---

**Last Updated:** February 3, 2026  
**Version:** 2.0.0  
**Status:** Production Ready ✅
