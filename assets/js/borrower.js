// Toggle sidebar on mobile
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}

// Search assets
function searchAssets() {
    let search = document.getElementById('search').value;
    let category = document.getElementById('category') ? document.getElementById('category').value : '';
    window.location.href = `available_assets.php?search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}`;
}

// Filter by category
function filterByCategory() {
    searchAssets();
}

// Check availability before showing borrow modal
function borrowAsset(id, name, maxQty) {
    fetch('ajax/check_availability.php?asset_id=' + id + '&quantity=1')
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                document.getElementById('asset_id').value = id;
                document.getElementById('asset_name').value = name;
                document.getElementById('max_qty').innerText = maxQty;
                document.getElementById('quantity').max = maxQty;
                document.getElementById('quantity').value = 1;
                document.getElementById('borrowModal').style.display = 'block';
            } else {
                alert(data.message || 'This asset is not available at the moment.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error checking availability. Please try again.');
        });
}

// Update quantity and check availability in real-time
function updateQuantityCheck() {
    const assetId = document.getElementById('asset_id').value;
    const quantity = document.getElementById('quantity').value;
    
    if (assetId && quantity) {
        fetch(`ajax/check_availability.php?asset_id=${assetId}&quantity=${quantity}`)
            .then(response => response.json())
            .then(data => {
                const maxQtySpan = document.getElementById('max_qty');
                if (!data.available) {
                    document.getElementById('quantity').style.borderColor = '#ef4444';
                    if (maxQtySpan) {
                        maxQtySpan.style.color = '#ef4444';
                    }
                } else {
                    document.getElementById('quantity').style.borderColor = '#22c55e';
                    if (maxQtySpan) {
                        maxQtySpan.style.color = '#22c55e';
                    }
                }
            });
    }
}

// Request return
function requestReturn(borrowId) {
    if (confirm('Are you sure you want to request return of this item?')) {
        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        fetch('actions/return_request.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'borrow_id=' + borrowId
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload();
            } else {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        })
        .catch(error => {
            alert('Error: ' + error);
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }
}

// Cancel borrow request
function cancelRequest(borrowId) {
    if (confirm('Are you sure you want to cancel this request?')) {
        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
        
        fetch('actions/cancel_request.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'borrow_id=' + borrowId
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload();
            } else {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        })
        .catch(error => {
            alert('Error: ' + error);
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }
}

// Close modal
function closeModal() {
    document.getElementById('borrowModal').style.display = 'none';
    document.getElementById('borrowForm').reset();
    document.getElementById('quantity').style.borderColor = '';
}

// Mark notification as read
function markAsRead(notifId) {
    fetch('ajax/mark_notification_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'notification_id=' + notifId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Load more notifications
let currentPage = 1;
let loading = false;

function loadMoreNotifications() {
    if (loading) return;
    loading = true;
    currentPage++;
    
    fetch(`ajax/get_notifications.php?page=${currentPage}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('notifications-list');
            if (container && data.length > 0) {
                data.forEach(notif => {
                    const notifDiv = document.createElement('div');
                    notifDiv.className = `notification-item ${notif.is_read ? 'read' : 'unread'}`;
                    notifDiv.setAttribute('onclick', `markAsRead(${notif.id})`);
                    notifDiv.innerHTML = `
                        <div class="notification-message">${escapeHtml(notif.message)}</div>
                        <div class="notification-date">
                            <i class="far fa-clock"></i> ${new Date(notif.created_at).toLocaleString()}
                        </div>
                    `;
                    container.appendChild(notifDiv);
                });
            }
            loading = false;
        })
        .catch(error => {
            console.error('Error:', error);
            loading = false;
        });
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Set minimum date for return (tomorrow)
const tomorrow = new Date();
tomorrow.setDate(tomorrow.getDate() + 1);
const dateInput = document.getElementById('expected_return_date');
if (dateInput) {
    dateInput.min = tomorrow.toISOString().split('T')[0];
}

// Add event listener for quantity change
const quantityInput = document.getElementById('quantity');
if (quantityInput) {
    quantityInput.addEventListener('change', updateQuantityCheck);
    quantityInput.addEventListener('keyup', updateQuantityCheck);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('borrowModal');
    if (event.target == modal) {
        closeModal();
    }
}

// Display success/error messages
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const error = urlParams.get('error');
    
    if (message) {
        showAlert(message, 'success');
    }
    if (error) {
        showAlert(error, 'error');
    }
});

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = message;
    alertDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// ========== EXPORT TO EXCEL FUNCTION ==========
// Add this function to export table data to Excel/CSV
function exportToExcel() {
    const table = document.getElementById('data-table');
    if (!table) {
        alert('No data to export');
        return;
    }
    
    let csvData = [];
    const headers = [];
    const headerCells = table.querySelectorAll('thead th');
    headerCells.forEach(th => {
        if (th.innerText.trim() !== 'Action') {
            headers.push(th.innerText.trim());
        }
    });
    csvData.push(headers);
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const rowData = [];
        const cells = row.querySelectorAll('td');
        cells.forEach((cell, index) => {
            if (index < 6) {
                let cellText = cell.innerText.trim();
                cellText = cellText.replace(/\n/g, ' ').replace(/\r/g, ' ');
                rowData.push(cellText);
            }
        });
        if (rowData.length > 0) {
            csvData.push(rowData);
        }
    });
    
    if (csvData.length <= 1) {
        alert('No data to export');
        return;
    }
    
    let csvContent = csvData.map(row => 
        row.map(cell => {
            if (cell.includes(';') || cell.includes('"') || cell.includes('\n')) {
                return `"${cell.replace(/"/g, '""')}"`;
            }
            return cell;
        }).join(';')
    ).join('\n');
    
    const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.href = url;
    link.setAttribute('download', `my_borrowed_items_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    alert('Export completed successfully!');
}

// ========== PRINT FUNCTION ==========
// Add this function to print the table
function printTable() {
    const table = document.getElementById('data-table');
    if (!table) {
        alert('No data to print');
        return;
    }
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print - My Borrowed Items</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #3b82f6; color: white; }
                .print-header { text-align: center; margin-bottom: 20px; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h2>ADSSU LAMS - My Borrowed Items</h2>
                <p>Printed on: ${new Date().toLocaleString()}</p>
                <p>User: ${document.querySelector('.user-info span')?.innerText || 'User'}</p>
            </div>
            ${table.outerHTML}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
    printWindow.close();
}