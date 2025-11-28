$(document).ready(function() {
    checkSession();
    loadDashboardData();
    $('#logoutBtn').click(logout);
});

function checkSession() {
    $.get('../../backend/api/auth.php?action=check')
        .done(function(response) {
            if (response.success && response.data && response.data.role === 'administrator') {
                $('#userName').text(response.data.full_name);
            } else {
                window.location.href = '../index.html';
            }
        })
        .fail(function() {
            window.location.href = '../index.html';
        });
}

function loadDashboardData() {
    showLoading();
    
    $.get('../../backend/api/reports.php?action=dashboard_stats')
        .done(function(response) {
            hideLoading();
            console.log('Admin dashboard response:', response);
            if (response.success) {
                updateStats(response.data);
                renderRecentSessions(response.recent_sessions || []);
                renderRecentActivity(response.recent_activity || []);
                renderCoursesTable(response.courses || []);
            } else {
                showError(response.error || 'Failed to load dashboard');
            }
        })
        .fail(function(error) {
            hideLoading();
            handleError(error);
        });
}

function updateStats(stats) {
    $('#totalStudents').text(stats.total_students || 0);
    $('#totalProfessors').text(stats.total_professors || 0);
    $('#totalCourses').text(stats.total_courses || 0);
    $('#totalSessions').text(stats.total_sessions || 0);
}

function renderRecentSessions(sessions) {
    if (!sessions || sessions.length === 0) {
        $('#recentSessions').html('<p class="text-center" style="color: var(--text-secondary);">No recent sessions</p>');
        return;
    }

    const html = sessions.slice(0, 5).map(session => `
        <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
            <div style="font-weight: 600; color: var(--dark);">${session.course_name}</div>
            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.25rem;">
                ${formatDate(session.session_date)} - ${formatTime(session.start_time)}
            </div>
            <div style="margin-top: 0.5rem;">
                <span class="badge badge-${session.status === 'open' ? 'success' : 'secondary'}">
                    ${session.status}
                </span>
            </div>
        </div>
    `).join('');

    $('#recentSessions').html(html);
}

function renderRecentActivity(activities) {
    if (!activities || activities.length === 0) {
        $('#recentActivity').html('<p class="text-center" style="color: var(--text-secondary);">No recent activity</p>');
        return;
    }

    const html = activities.slice(0, 5).map(activity => `
        <div style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
            <div style="font-weight: 600; color: var(--dark);">${activity.action}</div>
            <div style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.25rem;">
                ${activity.user_name || 'System'}
            </div>
            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">
                ${formatDate(activity.created_at)}
            </div>
        </div>
    `).join('');

    $('#recentActivity').html(html);
}

function renderCoursesTable(courses) {
    if (!courses || courses.length === 0) {
        $('#coursesTable').html('<tr><td colspan="5" class="text-center">No courses available</td></tr>');
        return;
    }

    const html = courses.map(course => `
        <tr>
            <td>${course.course_name}</td>
            <td>${course.professor_name}</td>
            <td>${course.student_count || 0}</td>
            <td>${course.session_count || 0}</td>
            <td>
                <span class="badge badge-${getAttendanceBadge(course.avg_attendance || 0)}">
                    ${course.avg_attendance || 0}%
                </span>
            </td>
        </tr>
    `).join('');

    $('#coursesTable').html(html);
}

function logout() {
    $.post('../../backend/api/auth.php', { action: 'logout' })
        .always(function() {
            window.location.href = '../index.html';
        });
}
