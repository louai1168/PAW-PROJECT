$(document).ready(function() {
    checkSession().done(function(response) {
        if (response.data) {
            window.currentUser = response.data;
            loadCourses();
        }
    });
    $('#logoutBtn').click(logout);
});

function checkSession() {
    return $.get('../../backend/api/auth.php?action=check')
        .done(function(response) {
            if (response.success && response.data.role === 'student') {
                $('#userName').text(response.data.full_name);
            } else {
                window.location.href = '../index.html';
            }
        })
        .fail(function() {
            window.location.href = '../index.html';
        });
}

function loadCourses() {
    if (!window.currentUser) return;
    
    $.get('../../backend/api/courses.php', { student_id: window.currentUser.user_id })
        .done(function(response) {
            console.log('Student courses response:', response);
            if (response.success && response.data) {
                console.log('Student courses data:', response.data);
                renderCourses(response.data);
                updateStats(response.data);
            }
        })
        .fail(handleError);
}

function renderCourses(courses) {
    if (!courses || courses.length === 0) {
        $('#coursesContainer').html(`
            <div class="card">
                <div class="text-center" style="padding: 3rem;">
                    <p style="color: var(--text-secondary);">You are not enrolled in any courses yet.</p>
                </div>
            </div>
        `);
        return;
    }

    const html = courses.map(course => {
        const attendance = course.attendance_percentage || 0;
        const badgeClass = getAttendanceBadge(attendance);
        
        return `
            <div class="card course-card">
                <h3>${course.course_name}</h3>
                <p><strong>Professor:</strong> ${course.professor_name}</p>
                <p><strong>Group:</strong> ${course.group_name || 'N/A'}</p>
                
                <div style="margin-top: 1rem;">
                    <div class="d-flex justify-between align-center mb-10">
                        <span>Attendance Rate</span>
                        <span class="badge ${badgeClass}">${attendance}%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${attendance}%"></div>
                    </div>
                </div>

                <div class="course-meta">
                    <div>
                        <small class="stat-label">Total Sessions</small>
                        <div><strong>${course.total_sessions || 0}</strong></div>
                    </div>
                    <div>
                        <small class="stat-label">Present</small>
                        <div><strong style="color: var(--success);">${course.present_count || 0}</strong></div>
                    </div>
                    <div>
                        <small class="stat-label">Absent</small>
                        <div><strong style="color: var(--danger);">${course.absent_count || 0}</strong></div>
                    </div>
                    <div>
                        <small class="stat-label">Late</small>
                        <div><strong style="color: var(--warning);">${course.late_count || 0}</strong></div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    $('#coursesContainer').html(html);
}

function updateStats(courses) {
    const totalCourses = courses.length;
    let totalSessions = 0;
    let totalPresent = 0;

    courses.forEach(course => {
        totalSessions += parseInt(course.total_sessions || 0);
        totalPresent += parseInt(course.present_count || 0);
    });

    const avgAttendance = calculateAttendanceRate(totalPresent, totalSessions);

    $('#totalCourses').text(totalCourses);
    $('#totalSessions').text(totalSessions);
    $('#avgAttendance').text(avgAttendance + '%');
}

function logout() {
    $.post('../../backend/api/auth.php', { action: 'logout' })
        .always(function() {
            window.location.href = '../index.html';
        });
}
