/**
 * public/assets/js/payments.js
 * Reworked Payments Management System
 * Handles modals, tab switching, and payment actions
 */

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializePaymentsPage();
});

/**
 * Initialize payments page
 */
function initializePaymentsPage() {
    // Set default next due date to next month
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    const nextMonthStr = nextMonth.toISOString().split('T')[0];
    
    document.getElementById('paidDueDate').value = nextMonthStr;
    document.getElementById('partialDueDate').value = nextMonthStr;
    document.getElementById('notpaidDueDate').value = nextMonthStr;
    
    // Close modals when clicking outside
    window.addEventListener('click', handleOutsideClick);
}

/**
 * Show specific tab
 * @param {string} tabName - Name of the tab to show (payments or arrears)
 */
function showTab(tabName) {
    // Hide all tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Remove active from all buttons
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    // Show selected tab
    const selectedTab = document.getElementById(tabName + '-tab');
    if (selectedTab) {
        selectedTab.classList.add('active');
    }
    
    // Highlight selected button
    event.target.closest('.tab-btn').classList.add('active');
}

/**
 * Handle payment action dropdown selection
 * @param {HTMLSelectElement} selectElement - The select element
 * @param {number} leaseId - The lease ID
 */
function handlePaymentAction(selectElement, leaseId) {
    const action = selectElement.value;
    
    if (!action) {
        return;
    }
    
    // Reset select
    selectElement.value = '';
    
    // Open appropriate modal based on action
    if (action === 'paid') {
        openPaidModal(leaseId);
    } else if (action === 'partial') {
        openPartialModal(leaseId);
    } else if (action === 'notpaid') {
        openNotPaidModal(leaseId);
    }
}

/**
 * Open "Mark as Paid" modal
 * @param {number} leaseId - The lease ID
 */
function openPaidModal(leaseId) {
    const modal = document.getElementById('paidModal');
    document.getElementById('paidLeaseId').value = leaseId;
    
    // Set default next due date
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    document.getElementById('paidDueDate').value = nextMonth.toISOString().split('T')[0];
    
    // Clear previous values
    document.getElementById('paidNextAmount').value = '';
    
    openModal(modal);
}

/**
 * Open "Mark as Partial Paid" modal
 * @param {number} leaseId - The lease ID
 */
function openPartialModal(leaseId) {
    const modal = document.getElementById('partialModal');
    document.getElementById('partialLeaseId').value = leaseId;
    
    // Set default next due date
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    document.getElementById('partialDueDate').value = nextMonth.toISOString().split('T')[0];
    
    // Clear previous values
    document.getElementById('partialPaidAmount').value = '';
    document.getElementById('partialNextAmount').value = '';
    
    openModal(modal);
}

/**
 * Open "Mark as Not Paid" modal
 * @param {number} leaseId - The lease ID
 */
function openNotPaidModal(leaseId) {
    const modal = document.getElementById('notpaidModal');
    document.getElementById('notpaidLeaseId').value = leaseId;
    
    // Set default next due date
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    document.getElementById('notpaidDueDate').value = nextMonth.toISOString().split('T')[0];
    
    // Clear previous values
    document.getElementById('notpaidNextAmount').value = '';
    
    openModal(modal);
}

/**
 * Open a modal
 * @param {HTMLElement} modal - The modal element
 */
function openModal(modal) {
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('show');
    }
}

/**
 * Close a modal
 * @param {string} modalId - The ID of the modal to close
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
}

/**
 * Handle click outside modal
 * @param {Event} event - The click event
 */
function handleOutsideClick(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        event.target.classList.remove('show');
    }
}

/**
 * Show arrears history for a lease
 * @param {number} leaseId - The lease ID
 */
function showArrearsHistory(leaseId) {
    const modal = document.getElementById('arrearsHistoryModal');
    const contentDiv = document.getElementById('arrearsHistoryContent');
    
    // Show loading state
    contentDiv.innerHTML = '<p style="text-align: center; color: #999;">Loading arrears history...</p>';
    
    openModal(modal);
    
    // Fetch arrears history
    fetch('/rentflow/api/arrears_history.php?lease_id=' + encodeURIComponent(leaseId), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            contentDiv.innerHTML = '<div class="alert alert-error"><i class="material-icons">error</i>' + 
                                   'Error: ' + htmlEscape(data.error) + '</div>';
            return;
        }
        
        let html = '<div style="padding: 20px;">';
        
        // Display summary
        html += '<div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #efefef;">';
        html += '<p><strong>Total Penalties Applied:</strong> ₱' + 
                (data.total_penalties || 0).toFixed(2) + '</p>';
        html += '</div>';
        
        // Display history
        if (data.history && data.history.length > 0) {
            html += '<table class="table" style="margin: 0;">';
            html += '<thead>';
            html += '<tr>';
            html += '<th>Date</th>';
            html += '<th>Amount</th>';
            html += '<th>Type</th>';
            html += '<th>Action</th>';
            html += '</tr>';
            html += '</thead>';
            html += '<tbody>';
            
            data.history.forEach(item => {
                html += '<tr>';
                html += '<td>' + htmlEscape(item.date) + '</td>';
                html += '<td>₱' + parseFloat(item.amount).toFixed(2) + '</td>';
                html += '<td><span class="badge badge-reason">' + htmlEscape(item.type) + '</span></td>';
                
                // Add pay button for unpaid dues
                if (item.type === 'Unpaid Due' || item.type === 'Penalty Applied') {
                    html += '<td>';
                    html += '<button class="btn btn-small" onclick="payArrear(' + 
                            leaseId + ', \'' + htmlEscape(item.date) + '\', ' + 
                            parseFloat(item.amount) + ')" style="width: auto;">';
                    html += '<i class="material-icons" style="font-size: 14px;">payment</i> Pay';
                    html += '</button>';
                    html += '</td>';
                } else {
                    html += '<td>—</td>';
                }
                
                html += '</tr>';
            });
            
            html += '</tbody>';
            html += '</table>';
        } else {
            html += '<p style="text-align: center; color: #999; padding: 30px 0;">No arrears history found.</p>';
        }
        
        html += '</div>';
        
        contentDiv.innerHTML = html;
    })
    .catch(error => {
        contentDiv.innerHTML = '<div class="alert alert-error"><i class="material-icons">error</i>' + 
                               'Error loading history: ' + htmlEscape(error.message) + '</div>';
    });
}

/**
 * Pay an arrear
 * @param {number} leaseId - The lease ID
 * @param {string} dueDate - The due date
 * @param {number} amount - The amount
 */
function payArrear(leaseId, dueDate, amount) {
    const amountPaid = prompt(
        'Enter amount to pay for this arrear:\n\nDue Date: ' + dueDate + '\nAmount: ₱' + amount.toFixed(2),
        amount.toFixed(2)
    );
    
    if (!amountPaid || isNaN(parseFloat(amountPaid)) || parseFloat(amountPaid) <= 0) {
        return;
    }
    
    const payload = new URLSearchParams();
    payload.append('lease_id', leaseId);
    payload.append('due_date', dueDate);
    payload.append('amount_paid', parseFloat(amountPaid));
    
    fetch('/rentflow/api/pay_arrear.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: payload.toString()
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Arrear payment recorded successfully.');
            // Refresh the history
            showArrearsHistory(leaseId);
        } else {
            alert('Error: ' + (data.error || 'Unknown error occurred'));
        }
    })
    .catch(error => {
        alert('Error processing payment: ' + error.message);
    });
}

/**
 * Utility function to escape HTML special characters
 * @param {string} text - Text to escape
 * @returns {string} - Escaped text
 */
function htmlEscape(text) {
    if (!text) return '';
    
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Format currency
 * @param {number} value - The value to format
 * @returns {string} - Formatted currency string
 */
function formatCurrency(value) {
    return '₱' + parseFloat(value).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Format date (YYYY-MM-DD)
 * @param {string|Date} date - The date to format
 * @returns {string} - Formatted date
 */
function formatDate(date) {
    if (typeof date === 'string') {
        return date;
    }
    
    if (date instanceof Date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    return '';
}

/**
 * Get next month date as YYYY-MM-DD
 * @returns {string} - Next month date
 */
function getNextMonthDate() {
    const nextMonth = new Date();
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    return nextMonth.toISOString().split('T')[0];
}
