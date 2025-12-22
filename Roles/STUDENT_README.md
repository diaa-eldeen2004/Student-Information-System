# Student Role Guide - Student Information System

This document provides a comprehensive guide for **Students** using the Student Information System.

---

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Workflow Examples](#workflow-examples)
4. [Getting Started](#getting-started)
5. [Common Issues](#common-issues)

---

## Overview

Students can manage their academic activities, view courses, submit assignments, track their progress, and communicate with instructors.

---

## Features

### 📊 Dashboard
- **Location**: `/student/dashboard`
- **Features**:
  - View GPA and academic statistics
  - See enrolled courses for current semester
  - View recent notifications
  - Check upcoming assignments (due in next 7 days)
  - View recently graded assignments with feedback

### 📚 Course Management
- **Location**: `/student/course`
- **Features**:
  - View all enrolled courses
  - Access course materials uploaded by doctors
  - View course assignments
  - See course details (code, name, credit hours, description)
  - View assigned doctor information

### 📅 Schedule Management
- **Location**: `/student/schedule`
- **Features**:
  - View weekly timetable with all class sessions
  - See course schedules organized by day
  - Preview timetable before enrollment
  - View available sections for enrollment
  - Request enrollment in courses
  - Check enrollment request status (pending/approved/rejected)
  - View semester and academic year filters

**Enrollment Process**:
1. Navigate to Schedule page
2. Select desired semester and academic year
3. Browse available sections
4. Click "Request Enrollment" on desired section
5. System checks:
   - Prerequisites completion
   - Schedule conflicts
   - Capacity availability
   - Existing enrollments
6. IT Officer reviews and approves/rejects request

### 📝 Assignments
- **Location**: `/student/assignments`
- **Features**:
  - View all assignments from enrolled courses
  - Filter by status:
    - **Pending**: Not yet submitted
    - **Submitted**: Submitted but not graded
    - **Graded**: Graded with feedback
    - **Overdue**: Past due date
  - Submit assignments with file upload
  - View assignment details (title, description, due date, max points)
  - Download assignment files (if provided by doctor)
  - View grades and feedback after grading
  - Resubmit assignments (clears previous grade)

**File Upload Requirements**:
- Allowed formats: PDF, DOC, DOCX, TXT, ZIP, RAR
- Maximum file size: 10MB
- File is stored securely on server

### 📆 Calendar
- **Location**: `/student/calendar`
- **Features**:
  - Monthly calendar view
  - View all assignments with due dates
  - See calendar events
  - View upcoming events (next 30 days)
  - Navigate between months
  - Color-coded events by type

### 🔔 Notifications
- **Location**: `/student/notifications`
- **Features**:
  - View all notifications
  - Mark notifications as read
  - Filter by notification type (info, success, warning, error)
  - See unread notification count
  - View notification history

### 💬 Send Messages
- **Location**: `/student/send-notification`
- **Features**:
  - Send messages to doctors from enrolled courses
  - Send messages to administrators
  - Select multiple recipients
  - Choose notification type
  - Add title and message content

### 👤 Profile
- **Location**: `/student/profile`
- **Features**:
  - View personal information
  - See student number
  - View GPA
  - Check major and minor
  - View admission date
  - See account status

---

## Workflow Examples

### Enrolling in a Course

1. Go to Schedule page (`/student/schedule`)
2. Select semester (Spring/Fall) and year
3. Browse available sections
4. Click "Request Enrollment" on desired section
5. Wait for IT Officer approval
6. Receive notification when approved/rejected

**Important Notes**:
- You can only enroll in one schedule per semester
- Prerequisites must be completed before enrollment
- System checks for schedule conflicts automatically
- Enrollment requests must be approved by IT Officer

### Submitting an Assignment

1. Go to Assignments page (`/student/assignments`)
2. Find assignment in "Pending" section
3. Click "Submit Assignment"
4. Upload file (PDF, DOC, DOCX, TXT, ZIP, RAR)
5. Confirm submission
6. Assignment moves to "Submitted" section
7. Doctor grades and provides feedback
8. View grade in "Graded" section

**Tips**:
- Submit assignments before the due date to avoid overdue status
- You can resubmit assignments, but this will clear the previous grade
- Check file size before uploading (max 10MB)
- Keep a copy of your submitted file

### Viewing Course Materials

1. Go to Course page (`/student/course`)
2. Select a course from your enrolled courses
3. View course materials uploaded by the doctor
4. Download materials as needed
5. View course assignments

### Tracking Academic Progress

1. Go to Dashboard (`/student/dashboard`)
2. View your current GPA
3. Check enrolled courses for current semester
4. Review upcoming assignments (next 7 days)
5. See recently graded assignments with feedback

---

## Getting Started

### First Time Login

1. **Login**: Use your student account credentials at `/auth/login`
2. **Dashboard**: View your academic overview at `/student/dashboard`
3. **Profile**: Check your profile information at `/student/profile`
4. **Schedule**: Browse available courses at `/student/schedule`
5. **Assignments**: Check for assignments at `/student/assignments`

### Daily Usage

1. **Check Dashboard**: Start your day by checking the dashboard for updates
2. **Review Notifications**: Check for new notifications
3. **View Schedule**: Check your class schedule
4. **Submit Assignments**: Complete and submit pending assignments
5. **Check Calendar**: Review upcoming deadlines

### Weekly Tasks

1. **Review Assignments**: Check all assignments and their due dates
2. **Submit Work**: Complete and submit assignments before deadlines
3. **Check Grades**: Review graded assignments and feedback
4. **View Materials**: Download and review course materials
5. **Communicate**: Send messages to doctors if needed

---

## Common Issues

### Cannot Enroll in Course

**Possible Causes**:
- Prerequisites not completed
- Schedule is full (capacity reached)
- Schedule conflicts with existing enrollment
- Already enrolled in a schedule for this semester
- Pending enrollment request already exists

**Solutions**:
1. Check prerequisites completion status
2. Verify schedule capacity
3. Check for schedule conflicts
4. Ensure no existing enrollment for the semester
5. Check enrollment request status

### Cannot Submit Assignment

**Possible Causes**:
- Assignment deadline has passed
- File size exceeds 10MB limit
- Invalid file format
- Not enrolled in the course

**Solutions**:
1. Check assignment due date
2. Compress file if too large
3. Ensure file format is allowed (PDF, DOC, DOCX, TXT, ZIP, RAR)
4. Verify course enrollment

### Cannot View Course Materials

**Possible Causes**:
- Not enrolled in the course
- Materials not uploaded by doctor yet
- Course access restricted

**Solutions**:
1. Verify course enrollment
2. Contact doctor if materials are missing
3. Check course access permissions

### Not Receiving Notifications

**Possible Causes**:
- Notifications marked as read
- Browser notifications disabled
- Email notifications not configured

**Solutions**:
1. Check notification settings
2. View notification history
3. Contact IT support if issues persist

---

## Authentication

- **Login URL**: `/auth/login`
- **Logout URL**: `/logout`
- Session-based authentication
- Automatic redirect to login if not authenticated

### Profile Management

- View your profile information
- Update personal details (name, email, phone)
- Change password
- View academic statistics

### Notifications System

- Receive system notifications automatically
- View notification history
- Mark notifications as read
- See unread notification count
- Filter notifications by type

---

## Important Notes

- All file uploads are validated for type and size
- Grades are automatically validated against maximum points
- Notifications are sent automatically for important events
- All actions are logged in the audit log
- Schedules support both single-day and weekly formats
- Enrollment requests require IT Officer approval
- **Students can only enroll in one schedule per semester**
- Assignment submissions can be resubmitted, but this clears the previous grade
- Check assignment due dates regularly to avoid overdue status

---

## Support

For technical support or questions, contact your system administrator or IT support.

**Common Contact Points**:
- IT Officer: For enrollment and schedule issues
- Doctor: For course content and assignment questions
- Administrator: For account and system issues

---

**Last Updated**: December 2024  
**System Version**: 1.0
