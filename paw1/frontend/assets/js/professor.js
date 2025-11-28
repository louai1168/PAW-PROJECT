let courses = [];
let selectedCourse = null;

function loadProfessorCourses() {
    if (!window.currentUser) return;
    
    showLoading();
    
    $.ajax({
        url: `${API_BASE}/courses.php`,
        method: 'GET',
        data: { professor_id: window.currentUser.user_id },
        success: function(response) {
            hideLoading();
            
            console.log('Professor courses response:', response);
            
            if (response.success) {
                courses = response.data;
                console.log('Courses data:', courses);
                displayCourses(courses);
            } else {
                showError('Failed to load courses');
            }
        },
        error: handleError
    });
}

function displayCourses(coursesData) {
    const container = $('#courseList');
    container.empty();
    
    console.log('Displaying courses:', coursesData);
    
    if (!coursesData || coursesData.length === 0) {
        container.append('<p>No courses found.</p>');
        return;
    }
    
    coursesData.forEach(course => {
        const card = $(`
            <div class="card">
                <h3>${course.course_name}</h3>
                <p><strong>Code:</strong> ${course.course_code}</p>
                <p><strong>Semester:</strong> ${course.semester} | <strong>Year:</strong> ${course.year}</p>
                <p><strong>Groups:</strong> ${course.group_count || 0} | <strong>Sessions:</strong> ${course.session_count || 0}</p>
                <div class="d-flex gap-10 mt-10">
                    <button class="btn btn-primary btn-sm create-session-btn" data-course-id="${course.course_id}">
                        Create Session
                    </button>
                    <button class="btn btn-success btn-sm view-sessions-btn" data-course-id="${course.course_id}">
                        View Sessions
                    </button>
                    <a href="summary.html?course_id=${course.course_id}" class="btn btn-sm">
                        View Report
                    </a>
                </div>
            </div>
        `);
        
        container.append(card);
    });
}

function filterCourses(searchTerm) {
    if (!searchTerm) {
        displayCourses(courses);
        return;
    }
    
    const filtered = courses.filter(course => {
        return course.course_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
               course.course_code.toLowerCase().includes(searchTerm.toLowerCase());
    });
    
    displayCourses(filtered);
}

function loadCourseGroups(courseId) {
    $.ajax({
        url: `${API_BASE}/courses.php`,
        method: 'GET',
        data: { course_id: courseId },
        success: function(response) {
            if (response.success && response.data.groups) {
                const groupSelect = $('#sessionGroup');
                groupSelect.empty().append('<option value="">Select Group</option>');
                
                response.data.groups.forEach(group => {
                    groupSelect.append(`<option value="${group.group_id}">${group.group_name} (${group.schedule_day} ${group.schedule_time})</option>`);
                });
            }
        }
    });
}

function createSession(formData) {
    showLoading();
    
    $.ajax({
        url: `${API_BASE}/attendance.php`,
        method: 'POST',
        data: formData,
        success: function(response) {
            hideLoading();
            
            if (response.success) {
                showSuccess('Session created successfully!');
                $('#createSessionModal').removeClass('show');
                clearForm('createSessionForm');
                
                window.location.href = `session.html?session_id=${response.session_id}`;
            } else {
                showError(response.error || 'Failed to create session');
            }
        },
        error: handleError
    });
}

$(document).ready(function() {
    const today = new Date().toISOString().split('T')[0];
    $('#sessionDate').val(today);
    
    checkAuth().done(function(response) {
        if (!window.currentUser && response.data) {
            window.currentUser = response.data;
        }
        
        loadProfessorCourses();
        
        if (courses.length > 0) {
            const courseSelect = $('#sessionCourse');
            courses.forEach(course => {
                courseSelect.append(`<option value="${course.course_id}">${course.course_name} (${course.course_code})</option>`);
            });
        }
    });
    
    $('#searchCourse').on('keyup', debounce(function() {
        filterCourses($(this).val());
    }, 300));
    
    $('#createSessionBtn').on('click', function() {
        $('#createSessionModal').addClass('show');
        
        const courseSelect = $('#sessionCourse');
        courseSelect.empty().append('<option value="">Select Course</option>');
        courses.forEach(course => {
            courseSelect.append(`<option value="${course.course_id}">${course.course_name} (${course.course_code})</option>`);
        });
    });
    
    $(document).on('click', '.create-session-btn', function() {
        const courseId = $(this).data('course-id');
        $('#createSessionModal').addClass('show');
        
        const courseSelect = $('#sessionCourse');
        courseSelect.empty().append('<option value="">Select Course</option>');
        courses.forEach(course => {
            courseSelect.append(`<option value="${course.course_id}">${course.course_name} (${course.course_code})</option>`);
        });
        
        $('#sessionCourse').val(courseId).trigger('change');
    });
    
    $('.modal-close').on('click', function() {
        $(this).closest('.modal').removeClass('show');
    });
    
    $('.modal').on('click', function(e) {
        if ($(e.target).hasClass('modal')) {
            $(this).removeClass('show');
        }
    });
    
    $('#sessionCourse').on('change', function() {
        const courseId = $(this).val();
        if (courseId) {
            loadCourseGroups(courseId);
        } else {
            $('#sessionGroup').empty().append('<option value="">Select Group</option>');
        }
    });
    
    $('#createSessionForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm('createSessionForm')) {
            showError('Please fill in all required fields');
            return;
        }
        
        const formData = {
            action: 'create_session',
            course_id: $('#sessionCourse').val(),
            group_id: $('#sessionGroup').val(),
            session_date: $('#sessionDate').val(),
            session_type: $('#sessionType').val()
        };
        
        createSession(formData);
    });
    
    $(document).on('click', '.view-sessions-btn', function() {
        const courseId = $(this).data('course-id');
        window.location.href = `summary.html?course_id=${courseId}`;
    });
});
