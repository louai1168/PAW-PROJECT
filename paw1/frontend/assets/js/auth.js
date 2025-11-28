const currentPath = window.location.pathname;
const isInSubfolder = currentPath.includes('/professor/') || 
                      currentPath.includes('/student/') || 
                      currentPath.includes('/admin/');
const API_BASE = isInSubfolder ? '../../backend/api' : '../backend/api';

function checkAuth() {
    return $.ajax({
        url: `${API_BASE}/auth.php?action=check`,
        method: 'GET'
    });
}

function login(username, password) {
    return $.ajax({
        url: `${API_BASE}/auth.php?action=login`,
        method: 'POST',
        data: {
            action: 'login',
            username: username,
            password: password
        }
    });
}

function logout() {
    $.ajax({
        url: `${API_BASE}/auth.php?action=logout`,
        method: 'POST',
        data: { action: 'logout' },
        success: function() {
            window.location.href = '../index.html';
        }
    });
}

function getCurrentUser() {
    return checkAuth();
}

function redirectByRole(role) {
    switch(role) {
        case 'professor':
            window.location.href = 'professor/home.html';
            break;
        case 'student':
            window.location.href = 'student/home.html';
            break;
        case 'administrator':
            window.location.href = 'admin/home.html';
            break;
        default:
            window.location.href = 'index.html';
    }
}

$(document).ready(function() {
    if ($('#loginForm').length > 0) {
        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            
            const username = $('#username').val();
            const password = $('#password').val();
            
            if (!username || !password) {
                $('#errorMessage').text('Please enter username and password').show();
                return;
            }
            
            showLoading();
            $('#errorMessage').hide();
            
            login(username, password)
                .done(function(response) {
                    hideLoading();
                    
                    if (response.success && response.data) {
                        console.log('Login successful, role:', response.data.role);
                        setTimeout(function() {
                            redirectByRole(response.data.role);
                        }, 100);
                    } else {
                        $('#errorMessage').text(response.error || 'Login failed').show();
                    }
                })
                .fail(function(xhr) {
                    hideLoading();
                    const error = xhr.responseJSON?.error || 'Login failed. Please try again.';
                    $('#errorMessage').text(error).show();
                });
        });
    }
    
    $(document).on('click', '#logoutBtn', function(e) {
        e.preventDefault();
        logout();
    });

    if (window.location.pathname.includes('/professor/') || 
        window.location.pathname.includes('/student/') || 
        window.location.pathname.includes('/admin/')) {
        
        checkAuth()
            .fail(function() {
                window.location.href = '../index.html';
            })
            .done(function(response) {
                if (response.success && response.data) {
                    window.currentUser = response.data;
                    if ($('#userName').length > 0) {
                        $('#userName').text(response.data.full_name);
                    }
                } else {
                    window.location.href = '../index.html';
                }
            });
    }
});
