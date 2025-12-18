<?php
// HTTP method, path, controller@method
return [
    // Public pages
    ['GET', '/', 'Home@index'],
    ['GET', '/about', 'PublicPages@about'],
    ['GET', '/majors', 'PublicPages@majors'],
    ['GET', '/doctors', 'PublicPages@doctors'],
    ['GET', '/projects', 'PublicPages@projects'],
    ['GET', '/contact', 'PublicPages@contact'],

    // Authentication
    ['GET', '/auth/login', 'Auth@login'],
    ['POST', '/auth/login', 'Auth@login'],
    ['GET', '/auth/sign', 'Auth@sign'],
    ['POST', '/auth/sign', 'Auth@sign'],
    ['GET', '/auth/forgot-password', 'Auth@forgotPassword'],
    ['POST', '/auth/forgot-password', 'Auth@forgotPassword'],
    ['GET', '/logout', 'Auth@logout'],
    ['POST', '/logout', 'Auth@logout'],

    // Student routes
    ['GET', '/student/dashboard', 'Student@dashboard'],
    ['GET', '/student/schedule', 'Student@schedule'],
    ['GET', '/student/assignments', 'Student@assignments'],
    ['POST', '/student/assignments/upload', 'Student@uploadAssignment'],
    ['GET', '/student/calendar', 'Student@calendar'],
    ['GET', '/student/course', 'Student@course'],
    ['GET', '/student/notifications', 'Student@notifications'],
    ['GET', '/student/profile', 'Student@profile'],
    ['POST', '/student/enroll', 'Student@enroll'],
    ['GET', '/student/preview-timetable', 'Student@previewTimetable'],

    // Doctor routes
    ['GET', '/doctor/dashboard', 'Doctor@dashboard'],
    ['GET', '/doctor/course', 'Doctor@course'],
    ['GET', '/doctor/assignments', 'Doctor@assignments'],
    ['POST', '/doctor/assignments', 'Doctor@assignments'],
    ['GET', '/doctor/create-assignment', 'Doctor@createAssignment'],
    ['POST', '/doctor/create-assignment', 'Doctor@createAssignment'],
    ['GET', '/doctor/attendance', 'Doctor@attendance'],
    ['GET', '/doctor/take-attendance', 'Doctor@takeAttendance'],
    ['POST', '/doctor/take-attendance', 'Doctor@takeAttendance'],
    ['GET', '/doctor/notifications', 'Doctor@notifications'],
    ['POST', '/doctor/notifications', 'Doctor@notifications'],
    ['GET', '/doctor/send-notification', 'Doctor@sendNotification'],
    ['POST', '/doctor/send-notification', 'Doctor@sendNotification'],
    ['GET', '/doctor/profile', 'Doctor@profile'],
    ['POST', '/doctor/profile', 'Doctor@profile'],
    ['GET', '/doctor/create-course', 'Doctor@createCourse'],
    ['POST', '/doctor/create-course', 'Doctor@createCourse'],
    ['GET', '/doctor/edit-assignment', 'Doctor@editAssignment'],
    ['POST', '/doctor/edit-assignment', 'Doctor@editAssignment'],
    ['POST', '/doctor/update-grade', 'Doctor@updateGrade'],
    ['POST', '/doctor/toggle-visibility', 'Doctor@toggleVisibility'],
    ['GET', '/doctor/upload-material', 'Doctor@uploadMaterial'],
    ['POST', '/doctor/upload-material', 'Doctor@uploadMaterial'],
    ['GET', '/doctor/edit-material', 'Doctor@editMaterial'],
    ['POST', '/doctor/edit-material', 'Doctor@editMaterial'],

    // Advisor routes
    ['GET', '/advisor/dashboard', 'Advisor@dashboard'],

    // IT Officer routes
    ['GET', '/it/dashboard', 'ItOfficer@dashboard'],
    ['GET', '/it/schedule', 'ItOfficer@schedule'],
    ['POST', '/it/schedule', 'ItOfficer@schedule'],
    ['GET', '/it/enrollments', 'ItOfficer@enrollments'],
    ['POST', '/it/enrollments/approve', 'ItOfficer@approveEnrollment'],
    ['POST', '/it/enrollments/approve-all', 'ItOfficer@approveAllEnrollments'],
    ['POST', '/it/enrollments/reject', 'ItOfficer@rejectEnrollment'],
    ['GET', '/it/course', 'ItOfficer@course'],
    ['POST', '/it/course', 'ItOfficer@course'],
    ['GET', '/it/logs', 'ItOfficer@logs'],
    ['POST', '/it/logs', 'ItOfficer@logs'],
    ['GET', '/it/send-notification', 'ItOfficer@sendNotification'],
    ['POST', '/it/send-notification', 'ItOfficer@sendNotification'],

    // Migration (development only - remove in production)
    ['GET', '/migrate', 'Migrate@run'],
    ['GET', '/migrate/run', 'Migrate@runMigration'],
    ['POST', '/migrate/run', 'Migrate@runMigration'],

    // Admin routes
    ['GET', '/admin/dashboard', 'Admin@dashboard'],
    ['GET', '/admin/calendar', 'Admin@calendar'],
    ['POST', '/admin/calendar', 'Admin@calendar'],
    ['GET', '/admin/profile', 'Admin@profile'],
    ['POST', '/admin/profile', 'Admin@profile'],
    ['GET', '/admin/reports', 'Admin@reports'],
    ['GET', '/admin/manage-student', 'Admin@manageStudent'],
    ['POST', '/admin/manage-student', 'Admin@manageStudent'],
    ['GET', '/admin/manage-doctor', 'Admin@manageDoctor'],
    ['POST', '/admin/manage-doctor', 'Admin@manageDoctor'],
    ['GET', '/admin/manage-course', 'Admin@manageCourse'],
    ['POST', '/admin/manage-course', 'Admin@manageCourse'],
    ['GET', '/admin/manage-advisor', 'Admin@manageAdvisor'],
    ['POST', '/admin/manage-advisor', 'Admin@manageAdvisor'],
    ['GET', '/admin/manage-it', 'Admin@manageIt'],
    ['POST', '/admin/manage-it', 'Admin@manageIt'],
    ['GET', '/admin/manage-admin', 'Admin@manageAdmin'],
    ['POST', '/admin/manage-admin', 'Admin@manageAdmin'],
    ['GET', '/admin/manage-user', 'Admin@manageUser'],
    ['POST', '/admin/manage-user', 'Admin@manageUser'],
    
    // Admin API endpoints for AJAX
    ['GET', '/admin/api/student', 'Admin@getStudentDetails'],
    ['GET', '/admin/api/doctor', 'Admin@getDoctorDetails'],
    ['GET', '/admin/api/advisor', 'Admin@getAdvisorDetails'],
    ['GET', '/admin/api/it', 'Admin@getItOfficerDetails'],
    ['GET', '/admin/api/admin', 'Admin@getAdminDetails'],
    ['GET', '/admin/api/user', 'Admin@getUserDetails'],
    ['GET', '/admin/api/course', 'Admin@getCourseDetails'],
    ['POST', '/admin/api/fix-it-autoincrement', 'Admin@fixItAutoIncrement'],
    
    // Debug routes (admin only)
    ['GET', '/debug/log', 'Debug@viewLog'],
    ['GET', '/debug/clear', 'Debug@clearLog'],
];
