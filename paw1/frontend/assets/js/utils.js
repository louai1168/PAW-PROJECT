function showLoading() {
    if ($('.loading-overlay').length === 0) {
        $('body').append('<div class="loading-overlay"><div class="spinner"></div></div>');
    }
}

function hideLoading() {
    $('.loading-overlay').remove();
}

function showToast(message, type = 'info') {
    const toast = $(`<div class="toast toast-${type}">${message}</div>`);
    $('body').append(toast);
    
    setTimeout(() => {
        toast.addClass('show');
    }, 100);
    
    setTimeout(() => {
        toast.removeClass('show');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

function showSuccess(message) {
    showToast(message, 'success');
}

function showError(message) {
    showToast(message, 'error');
}

function handleError(xhr, status, error) {
    hideLoading();
    
    let errorMessage = 'An error occurred';
    
    if (xhr.responseJSON && xhr.responseJSON.error) {
        errorMessage = xhr.responseJSON.error;
    } else if (xhr.statusText) {
        errorMessage = xhr.statusText;
    }
    
    showError(errorMessage);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-GB');
}

function formatTime(timeString) {
    if (!timeString) {
        return 'N/A';
    }

    const [hours = '00', minutes = '00'] = timeString.split(':');
    const date = new Date();
    date.setHours(parseInt(hours, 10), parseInt(minutes, 10), 0, 0);

    return date.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

function validateForm(formId) {
    let isValid = true;
    
    $(`#${formId} [required]`).each(function() {
        if (!$(this).val() || $(this).val().trim() === '') {
            $(this).addClass('error');
            isValid = false;
        } else {
            $(this).removeClass('error');
        }
    });
    
    return isValid;
}

function clearForm(formId) {
    $(`#${formId}`)[0].reset();
    $(`#${formId} .error`).removeClass('error');
}

function calculateAttendanceRate(present, total) {
    if (total === 0) return 0;
    return Math.round((present / total) * 100);
}

function getAttendanceBadge(percentage) {
    if (percentage >= 75) return 'badge-success';
    if (percentage >= 50) return 'badge-warning';
    return 'badge-danger';
}

function getStatusBadge(status) {
    const badges = {
        'present': '<span class="badge badge-success">Present</span>',
        'absent': '<span class="badge badge-danger">Absent</span>',
        'late': '<span class="badge badge-warning">Late</span>',
        'excused': '<span class="badge badge-info">Excused</span>'
    };
    
    return badges[status] || '<span class="badge">Unknown</span>';
}

function getJustificationStatusBadge(status) {
    const badges = {
        'pending': '<span class="badge badge-warning">Pending</span>',
        'approved': '<span class="badge badge-success">Approved</span>',
        'rejected': '<span class="badge badge-danger">Rejected</span>'
    };
    
    return badges[status] || '<span class="badge">Unknown</span>';
}

function sortTable(table, column, direction = 'asc') {
    const tbody = table.find('tbody');
    const rows = tbody.find('tr').toArray();
    
    rows.sort((a, b) => {
        const aValue = $(a).find('td').eq(column).text();
        const bValue = $(b).find('td').eq(column).text();
        
        if (direction === 'asc') {
            return aValue.localeCompare(bValue, undefined, { numeric: true });
        } else {
            return bValue.localeCompare(aValue, undefined, { numeric: true });
        }
    });
    
    tbody.empty().append(rows);
}

function filterTable(table, searchTerm) {
    const rows = table.find('tbody tr');
    
    rows.each(function() {
        const text = $(this).text().toLowerCase();
        if (text.includes(searchTerm.toLowerCase())) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

function generatePagination(container, currentPage, totalPages, callback) {
    container.empty();
    
    if (totalPages <= 1) return;
    
    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    container.append(`<button class="btn btn-sm" ${prevDisabled} data-page="${currentPage - 1}">Previous</button>`);
    
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const active = i === currentPage ? 'active' : '';
        container.append(`<button class="btn btn-sm ${active}" data-page="${i}">${i}</button>`);
    }
    
    const nextDisabled = currentPage === totalPages ? 'disabled' : '';
    container.append(`<button class="btn btn-sm" ${nextDisabled} data-page="${currentPage + 1}">Next</button>`);
   
    container.find('button').on('click', function() {
        if (!$(this).prop('disabled')) {
            const page = parseInt($(this).data('page'));
            callback(page);
        }
    });
}

function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

function exportTableToCSV(table, filename) {
    const rows = [];
    
    const headers = [];
    table.find('thead th').each(function() {
        headers.push($(this).text());
    });
    rows.push(headers);
    
    table.find('tbody tr:visible').each(function() {
        const row = [];
        $(this).find('td').each(function() {
            row.push($(this).text());
        });
        rows.push(row);
    });
    
    let csvContent = '';
    rows.forEach(row => {
        csvContent += row.join(',') + '\n';
    });
   
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function exportToCSV(data, filename) {
    if (!data || data.length === 0) {
        showError('No data to export');
        return;
    }
    
    const headers = Object.keys(data[0]);
    const rows = data.map(row => 
        headers.map(header => {
            const value = row[header] || '';
            return typeof value === 'string' && (value.includes(',') || value.includes('"'))
                ? `"${value.replace(/"/g, '""')}"` 
                : value;
        }).join(',')
    );
    
    const csvContent = [headers.join(','), ...rows].join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'export.csv';
    a.style.visibility = 'hidden';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
