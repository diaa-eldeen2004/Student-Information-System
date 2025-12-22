# Doctor Role Guide - Student Information System

This document provides a comprehensive guide for **Doctors (Instructors)** using the Student Information System.

---

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Workflow Examples](#workflow-examples)
4. [Getting Started](#getting-started)
5. [Common Issues](#common-issues)

---

## Overview

Doctors (instructors) can manage their courses, create assignments, track attendance, grade submissions, and communicate with students.

---

## Features

### 📊 Dashboard
- **Location**: `/doctor/dashboard`
- **Features**:
  - View assigned courses/sections
  - See recent assignments
  - Quick access to key functions
  - View course statistics

### 📚 Course Management
- **Location**: `/doctor/course`
- **Features**:
  - View all assigned courses
  - See sections for each course
  - View course details
  - Access course materials
  - See enrolled students per section

### 📝 Assignment Management
- **Location**: `/doctor/assignments`
- **Features**:
  - View all assignments (active and completed)
  - Filter by course, status, or type
  - See assignment history
  - View submission statistics
  - See student submissions with details

**Assignment Types**:
- Homework
- Project
- Quiz
- Exam
- Lab

### ➕ Create Assignment
- **Location**: `/doctor/create-assignment`
- **Features**:
  - Create new assignments/quizzes
  - Select course and section
  - Set assignment title and description
  - Set due date and time
  - Set maximum points
  - Choose assignment type
  - Upload assignment file (optional)
  - Set visibility settings:
    - Visible immediately or scheduled
    - Visible for specific duration (hours/days)
  - Preview recent assignments

**Assignment Builder Features**:
- Step-by-step assignment creation
- File attachment support
- Semester and academic year auto-detection
- Visibility control

### ✏️ Edit Assignment
- **Location**: `/doctor/edit-assignment?id={assignment_id}`
- **Features**:
  - Update assignment details
  - Change due date
  - Modify points
  - Update description
  - Replace assignment file
  - Change visibility settings

### 📊 Grade Assignments
- **Location**: `/doctor/assignments`
- **Features**:
  - View all student submissions
  - Grade assignments with points
  - Add feedback comments
  - Validate grades (cannot exceed max points)
  - Auto-notify students when graded
  - View submission files
  - See submission date and time

**Grading Process**:
1. Go to Assignments page
2. Find assignment with submissions
3. Click on submission
4. Enter grade (0 to max points)
5. Add feedback (optional)
6. Submit grade
7. Student receives notification automatically

### ✅ Attendance Management
- **Location**: `/doctor/attendance`
- **Features**:
  - View all assigned sections
  - See attendance statistics per section
  - View student count per section
  - Access attendance recording

### 📋 Take Attendance
- **Location**: `/doctor/take-attendance?section_id={id}`
- **Features**:
  - Record attendance for specific date
  - Mark students as:
    - **Present**: Attended class
    - **Absent**: Did not attend
    - **Late**: Arrived late
    - **Excused**: Absence excused
  - Add notes per student
  - View previous attendance records
  - See enrolled students list

### 📤 Upload Course Materials
- **Location**: `/doctor/upload-material`
- **Features**:
  - Upload files for courses
  - Select course and section
  - Add title and description
  - Choose material type
  - Upload various file formats
  - Materials visible to enrolled students

### ✏️ Edit Materials
- **Location**: `/doctor/edit-material?id={material_id}`
- **Features**:
  - Update material title and description
  - Replace material file
  - Update material type

### 🔔 Notifications
- **Location**: `/doctor/notifications`
- **Features**:
  - View all notifications
  - Mark as read
  - See unread count
  - Filter notifications
  - Receive notifications for:
    - New assignment submissions
    - Student messages
    - System updates

### 💬 Send Notifications
- **Location**: `/doctor/send-notification`
- **Features**:
  - Send messages to enrolled students
  - Select multiple students
  - Choose notification type
  - Add title and message

### 👤 Profile
- **Location**: `/doctor/profile`
- **Features**:
  - View and update personal information
  - Update department
  - Change password
  - View statistics:
    - Number of assigned sections
    - Number of assignments created

---

## Workflow Examples

### Creating an Assignment

1. Go to Create Assignment page (`/doctor/create-assignment`)
2. Select course and section
3. Enter assignment details:
   - Title
   - Description
   - Due date and time
   - Maximum points
4. Choose assignment type (homework, project, quiz, exam, lab)
5. Upload file (optional)
6. Set visibility settings:
   - Visible immediately or scheduled
   - Duration (hours/days)
7. Submit
8. Students receive notification (if visible)

**Tips**:
- Set clear due dates and times
- Provide detailed descriptions
- Attach assignment files when needed
- Use visibility settings to control when students see assignments

### Grading Submissions

1. Go to Assignments page (`/doctor/assignments`)
2. Find assignment with submissions
3. Click on student submission
4. Review submitted file
5. Enter grade (0 to max points)
6. Add feedback (optional but recommended)
7. Submit grade
8. Student automatically notified

**Grading Best Practices**:
- Review submissions carefully
- Provide constructive feedback
- Grade consistently across all submissions
- Use the feedback field to explain grades
- Grades are automatically validated (cannot exceed max points)

### Recording Attendance

1. Go to Attendance page (`/doctor/attendance`)
2. Select section
3. Click "Take Attendance"
4. Select date
5. Mark each student's status:
   - Present
   - Absent
   - Late
   - Excused
6. Add notes if needed
7. Save attendance
8. Records stored for reporting

**Attendance Tips**:
- Record attendance regularly
- Use notes for important information
- Mark excused absences appropriately
- Review attendance statistics regularly

### Uploading Course Materials

1. Go to Upload Material page (`/doctor/upload-material`)
2. Select course and section
3. Enter title and description
4. Choose material type
5. Upload file
6. Submit
7. Materials visible to enrolled students

---

## Getting Started

### First Time Login

1. **Login**: Use your doctor account credentials at `/auth/login`
2. **Dashboard**: View your assigned courses at `/doctor/dashboard`
3. **Profile**: Update your profile information at `/doctor/profile`
4. **Courses**: Review your assigned courses at `/doctor/course`
5. **Assignments**: Check existing assignments at `/doctor/assignments`

### Daily Usage

1. **Check Dashboard**: Start your day by checking the dashboard
2. **Review Notifications**: Check for new assignment submissions
3. **Grade Submissions**: Grade submitted assignments
4. **Record Attendance**: Take attendance for classes
5. **Respond to Messages**: Reply to student messages

### Weekly Tasks

1. **Create Assignments**: Create new assignments for upcoming weeks
2. **Grade Submissions**: Review and grade all submitted work
3. **Record Attendance**: Take attendance for all classes
4. **Upload Materials**: Share course materials with students
5. **Communicate**: Send announcements to students

---

## Common Issues

### Cannot Grade Assignment

**Possible Causes**:
- Assignment doesn't belong to you
- Submission doesn't exist
- Grade exceeds maximum points

**Solutions**:
1. Verify assignment belongs to your course
2. Check that submission exists
3. Ensure grade doesn't exceed max points (system validates automatically)

### Cannot Create Assignment

**Possible Causes**:
- No sections assigned
- Course not available
- Missing required fields

**Solutions**:
1. Verify you have assigned sections
2. Check course availability
3. Ensure all required fields are filled

### Cannot Record Attendance

**Possible Causes**:
- Section not assigned to you
- No enrolled students
- Date selection issue

**Solutions**:
1. Verify section assignment
2. Check enrolled students list
3. Ensure date is selected correctly

### Students Cannot See Assignment

**Possible Causes**:
- Assignment visibility settings
- Assignment not published
- Students not enrolled

**Solutions**:
1. Check visibility settings
2. Ensure assignment is visible
3. Verify student enrollment

### Cannot Upload Materials

**Possible Causes**:
- File size too large
- Invalid file format
- Course not selected

**Solutions**:
1. Check file size limits
2. Verify file format
3. Ensure course is selected

---

## Authentication

- **Login URL**: `/auth/login`
- **Logout URL**: `/logout`
- Session-based authentication
- Automatic redirect to login if not authenticated

### Profile Management

- View your profile information
- Update personal details (name, email, phone)
- Update department information
- Change password
- View statistics (sections, assignments)

### Notifications System

- Receive notifications automatically for:
  - New assignment submissions
  - Student messages
  - System updates
- View notification history
- Mark notifications as read
- See unread notification count
- Filter notifications by type

---

## Important Notes

- All file uploads are validated for type and size
- Grades are automatically validated against maximum points
- Notifications are sent automatically when you grade assignments
- All actions are logged in the audit log
- Students receive automatic notifications when assignments are graded
- Attendance records are stored for reporting purposes
- Course materials are visible only to enrolled students
- You can only manage assignments for your assigned courses
- Assignment visibility can be controlled (immediate or scheduled)

---

## Best Practices

### Assignment Management

- Create assignments well in advance
- Provide clear instructions and descriptions
- Set realistic due dates
- Use appropriate assignment types
- Attach relevant files when needed

### Grading

- Grade consistently across all students
- Provide constructive feedback
- Grade in a timely manner
- Review submissions carefully
- Use the feedback field effectively

### Attendance

- Record attendance regularly
- Be consistent with attendance marking
- Use notes for important information
- Review attendance patterns

### Communication

- Respond to student messages promptly
- Send clear announcements
- Use appropriate notification types
- Keep students informed about course updates

---

## Support

For technical support or questions, contact your system administrator or IT support.

**Common Contact Points**:
- IT Officer: For course and schedule issues
- Administrator: For account and system issues
- System Support: For technical problems

---

**Last Updated**: December 2024  
**System Version**: 1.0
