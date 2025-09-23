// Hospital Management System - JavaScript Functions

// Initialize tooltips and popovers
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// Form validation functions
function validateForm(formId) {
    var form = document.getElementById(formId);
    if (!form) return true;

    var inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    var isValid = true;

    inputs.forEach(function(input) {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Email validation
function validateEmail(email) {
    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Phone number validation
function validatePhone(phone) {
    var re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(phone.replace(/[\s\-\(\)]/g, ''));
}

// Date validation
function validateDate(date) {
    var inputDate = new Date(date);
    var today = new Date();
    return inputDate >= today;
}

// Show loading spinner
function showLoading(button) {
    var originalText = button.innerHTML;
    button.innerHTML = '<span class="loading"></span> Loading...';
    button.disabled = true;
    return originalText;
}

// Hide loading spinner
function hideLoading(button, originalText) {
    button.innerHTML = originalText;
    button.disabled = false;
}

// Confirm delete action
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Format datetime
function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Search functionality
function searchTable(tableId, searchTerm) {
    var table = document.getElementById(tableId);
    if (!table) return;

    var rows = table.querySelectorAll('tbody tr');
    var searchLower = searchTerm.toLowerCase();

    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        if (text.includes(searchLower)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Sort table
function sortTable(tableId, columnIndex, type = 'string') {
    var table = document.getElementById(tableId);
    if (!table) return;

    var tbody = table.querySelector('tbody');
    var rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort(function(a, b) {
        var aValue = a.cells[columnIndex].textContent.trim();
        var bValue = b.cells[columnIndex].textContent.trim();

        if (type === 'number') {
            aValue = parseFloat(aValue) || 0;
            bValue = parseFloat(bValue) || 0;
        } else if (type === 'date') {
            aValue = new Date(aValue);
            bValue = new Date(bValue);
        }

        if (aValue < bValue) return -1;
        if (aValue > bValue) return 1;
        return 0;
    });

    rows.forEach(function(row) {
        tbody.appendChild(row);
    });
}

// Export table to CSV
function exportTableToCSV(tableId, filename) {
    var table = document.getElementById(tableId);
    if (!table) return;

    var csv = [];
    var rows = table.querySelectorAll('tr');

    rows.forEach(function(row) {
        var rowData = [];
        var cells = row.querySelectorAll('th, td');
        
        cells.forEach(function(cell) {
            rowData.push('"' + cell.textContent.replace(/"/g, '""') + '"');
        });
        
        csv.push(rowData.join(','));
    });

    var csvContent = csv.join('\n');
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    
    if (link.download !== undefined) {
        var url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// Print page
function printPage() {
    window.print();
}

// Show notification
function showNotification(message, type = 'info') {
    var alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-' + type + ' alert-dismissible fade show';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    var container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// AJAX helper function
function ajaxRequest(url, method = 'GET', data = null) {
    return new Promise(function(resolve, reject) {
        var xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    resolve(response);
                } catch (e) {
                    resolve(xhr.responseText);
                }
            } else {
                reject(xhr.statusText);
            }
        };
        
        xhr.onerror = function() {
            reject(xhr.statusText);
        };
        
        if (data) {
            xhr.send(JSON.stringify(data));
        } else {
            xhr.send();
        }
    });
}

// Chart.js helper for creating charts
function createChart(canvasId, type, data, options = {}) {
    var ctx = document.getElementById(canvasId);
    if (!ctx) return null;

    return new Chart(ctx, {
        type: type,
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            ...options
        }
    });
}

// Utility function to get URL parameters
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

// Utility function to set URL parameter
function setUrlParameter(name, value) {
    var url = new URL(window.location);
    url.searchParams.set(name, value);
    window.history.pushState({}, '', url);
}

// Debounce function for search inputs
function debounce(func, wait) {
    var timeout;
    return function executedFunction(...args) {
        var later = function() {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize search functionality
document.addEventListener('DOMContentLoaded', function() {
    var searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(function(input) {
        var tableId = input.getAttribute('data-table');
        if (tableId) {
            var debouncedSearch = debounce(function(searchTerm) {
                searchTable(tableId, searchTerm);
            }, 300);
            
            input.addEventListener('input', function() {
                debouncedSearch(this.value);
            });
        }
    });
}); 