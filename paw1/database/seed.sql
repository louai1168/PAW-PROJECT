USE attendance_system;

INSERT INTO `groups` (group_name, level, department) VALUES
('G1', 'L3', 'Computer Science'),
('G2', 'L3', 'Computer Science'),
('G1', 'M1', 'Software Engineering'),
('G2', 'M1', 'Software Engineering'),
('G1', 'L2', 'Information Systems');


INSERT INTO users (username, password_hash, email, full_name, role) VALUES

('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@univ-alger.dz', 'System Administrator', 'administrator'),

('prof_benali', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'benali@univ-alger.dz', 'Dr. Ahmed Benali', 'professor'),
('prof_mohamed', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mohamed@univ-alger.dz', 'Dr. Mohamed Boutaba', 'professor'),
('prof_fatima', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'fatima@univ-alger.dz', 'Dr. Fatima Zohra', 'professor'),

('student_001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'yacine.hamidi@univ-alger.dz', 'Yacine Hamidi', 'student'),
('student_002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'amina.khelifi@univ-alger.dz', 'Amina Khelifi', 'student'),
('student_003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'karim.benhamouda@univ-alger.dz', 'Karim Benhamouda', 'student'),
('student_004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sarah.mansouri@univ-alger.dz', 'Sarah Mansouri', 'student'),
('student_005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mehdi.zerrouki@univ-alger.dz', 'Mehdi Zerrouki', 'student'),
('student_006', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lina.boumediene@univ-alger.dz', 'Lina Boumediene', 'student'),
('student_007', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'raouf.belkacem@univ-alger.dz', 'Raouf Belkacem', 'student'),
('student_008', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'yasmine.touati@univ-alger.dz', 'Yasmine Touati', 'student'),
('student_009', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'walid.cherif@univ-alger.dz', 'Walid Cherif', 'student'),
('student_010', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nesrine.hadj@univ-alger.dz', 'Nesrine Hadj', 'student');

INSERT INTO administrators (admin_id, department, access_level) VALUES
(1, 'IT Department', 'super');

INSERT INTO professors (professor_id, department, title) VALUES
(2, 'Computer Science', 'Professor'),
(3, 'Computer Science', 'Associate Professor'),
(4, 'Information Systems', 'Assistant Professor');

INSERT INTO students (student_id, matricule, group_id, enrollment_year, level) VALUES
(5, '191931234567', 1, 2019, 'L3'),
(6, '191931234568', 1, 2019, 'L3'),
(7, '191931234569', 2, 2019, 'L3'),
(8, '191931234570', 2, 2019, 'L3'),
(9, '202131234571', 3, 2021, 'M1'),
(10, '202131234572', 3, 2021, 'M1'),
(11, '202131234573', 4, 2021, 'M1'),
(12, '202231234574', 5, 2022, 'L2'),
(13, '202231234575', 5, 2022, 'L2'),
(14, '202231234576', 5, 2022, 'L2');

INSERT INTO courses (course_code, course_name, professor_id, semester, year, credits) VALUES
('CS301', 'Advanced Web Programming', 2, '1', 2025, 6),
('CS302', 'Database Management Systems', 3, '1', 2025, 6),
('CS303', 'Software Engineering', 2, '2', 2025, 5),
('IS201', 'Information Systems Analysis', 4, '1', 2025, 4),
('CS401', 'Web Services and APIs', 3, '1', 2025, 5);

INSERT INTO course_groups (course_id, group_id, schedule_day, schedule_time) VALUES
(1, 1, 'Monday', '08:00-10:00'),
(1, 2, 'Monday', '10:00-12:00'),
(2, 1, 'Tuesday', '08:00-10:00'),
(2, 2, 'Tuesday', '10:00-12:00'),
(3, 1, 'Wednesday', '14:00-16:00'),
(4, 5, 'Thursday', '08:00-10:00'),
(5, 3, 'Friday', '08:00-10:00'),
(5, 4, 'Friday', '10:00-12:00');

INSERT INTO attendance_sessions (course_id, group_id, professor_id, session_date, session_type, status) VALUES

(1, 1, 2, '2025-11-10', 'lecture', 'closed'),
(1, 1, 2, '2025-11-17', 'lecture', 'closed'),
(1, 1, 2, '2025-11-24', 'lecture', 'closed'),
(1, 2, 2, '2025-11-10', 'lecture', 'closed'),
(1, 2, 2, '2025-11-17', 'lecture', 'closed'),

(2, 1, 3, '2025-11-11', 'lecture', 'closed'),
(2, 1, 3, '2025-11-18', 'lab', 'closed'),
(2, 2, 3, '2025-11-11', 'lecture', 'closed'),

(3, 1, 2, '2025-11-12', 'lecture', 'closed'),

(4, 5, 4, '2025-11-13', 'lecture', 'closed'),
(4, 5, 4, '2025-11-20', 'lecture', 'closed'),

(5, 3, 3, '2025-11-14', 'lecture', 'closed'),
(5, 3, 3, '2025-11-21', 'lecture', 'closed');

INSERT INTO attendance_records (session_id, student_id, status, participation_score, marked_at) VALUES
(1, 5, 'present', 5, '2025-11-10 08:05:00'),
(1, 6, 'present', 4, '2025-11-10 08:05:00');

INSERT INTO attendance_records (session_id, student_id, status, participation_score, marked_at) VALUES
(2, 5, 'present', 5, '2025-11-17 08:05:00'),
(2, 6, 'absent', 0, '2025-11-17 08:05:00');

INSERT INTO attendance_records (session_id, student_id, status, participation_score, marked_at) VALUES
(3, 5, 'present', 4, '2025-11-24 08:05:00'),
(3, 6, 'late', 2, '2025-11-24 08:05:00');

INSERT INTO attendance_records (session_id, student_id, status, participation_score, marked_at) VALUES
(4, 7, 'present', 5, '2025-11-10 10:05:00'),
(4, 8, 'present', 3, '2025-11-10 10:05:00');

INSERT INTO attendance_records (session_id, student_id, status, participation_score, marked_at) VALUES
(5, 7, 'absent', 0, '2025-11-17 10:05:00'),
(5, 8, 'present', 4, '2025-11-17 10:05:00');

INSERT INTO attendance_records (session_id, student_id, status, participation_score, marked_at) VALUES
(6, 5, 'present', 5, '2025-11-11 08:05:00'),
(6, 6, 'present', 4, '2025-11-11 08:05:00');

INSERT INTO attendance_records (session_id, student_id, status, participation_score, marked_at) VALUES
(7, 5, 'present', 5, '2025-11-18 08:05:00'),
(7, 6, 'present', 5, '2025-11-18 08:05:00');

INSERT INTO attendance_records (session_id, student_id, status, participation_score, marked_at) VALUES
(8, 7, 'present', 4, '2025-11-11 10:05:00'),
(8, 8, 'late', 2, '2025-11-11 10:05:00');

INSERT INTO attendance_records (session_id, student_id, status, participation_score, marked_at) VALUES
(10, 12, 'present', 4, '2025-11-13 08:05:00'),
(10, 13, 'present', 5, '2025-11-13 08:05:00'),
(10, 14, 'absent', 0, '2025-11-13 08:05:00');

INSERT INTO attendance_records (session_id, student_id, status, participation_score, marked_at) VALUES
(11, 12, 'present', 3, '2025-11-20 08:05:00'),
(11, 13, 'late', 3, '2025-11-20 08:05:00'),
(11, 14, 'present', 4, '2025-11-20 08:05:00');

INSERT INTO attendance_records (session_id, student_id, status, participation_score, marked_at) VALUES
(12, 9, 'present', 5, '2025-11-14 08:05:00'),
(12, 10, 'present', 4, '2025-11-14 08:05:00');

INSERT INTO attendance_records (session_id, student_id, status, participation_score, marked_at) VALUES
(13, 9, 'absent', 0, '2025-11-21 08:05:00'),
(13, 10, 'present', 5, '2025-11-21 08:05:00');

INSERT INTO justifications (student_id, session_id, reason, file_path, status, submitted_at, reviewed_at, reviewed_by, reviewer_notes) VALUES
(6, 2, 'Medical appointment with doctor certificate', 'uploads/justifications/cert_medical_001.pdf', 'approved', '2025-11-17 14:00:00', '2025-11-18 09:00:00', 2, 'Valid medical certificate'),
(7, 5, 'Family emergency', 'uploads/justifications/justif_002.pdf', 'pending', '2025-11-18 10:00:00', NULL, NULL, NULL),
(14, 10, 'Sick with flu', NULL, 'rejected', '2025-11-13 15:00:00', '2025-11-14 09:00:00', 4, 'No supporting document provided'),
(9, 13, 'Transportation strike', 'uploads/justifications/justif_004.pdf', 'pending', '2025-11-22 08:00:00', NULL, NULL, NULL);

INSERT INTO activity_logs (user_id, action, table_affected, record_id, ip_address) VALUES
(2, 'CREATE_SESSION', 'attendance_sessions', 1, '127.0.0.1'),
(2, 'MARK_ATTENDANCE', 'attendance_records', 1, '127.0.0.1'),
(3, 'CREATE_SESSION', 'attendance_sessions', 6, '127.0.0.1'),
(1, 'IMPORT_STUDENTS', 'students', NULL, '127.0.0.1'),
(6, 'SUBMIT_JUSTIFICATION', 'justifications', 1, '127.0.0.1');
