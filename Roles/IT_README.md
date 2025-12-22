# IT Officer Role Guide - Student Information System

This document provides a comprehensive guide for **IT Officers** using the Student Information System.

---

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Workflow Examples](#workflow-examples)
4. [Getting Started](#getting-started)
5. [Common Issues](#common-issues)

---

## Overview

IT Officers manage course schedules, approve enrollment requests, create courses, and maintain system logs.

---

## Features

### 📊 Dashboard
- **Location**: `/it/dashboard`
- **Features**:
  - View pending enrollment requests count
  - See system statistics:
    - Total courses
    - Total sections/schedules
    - Total doctors
    - Total students
  - View enrollment statistics:
    - Approved enrollments
    - Rejected enrollments
  - View recent activity logs
  - Quick access to key functions

### 📅 Schedule Management
- **Location**: `/it/schedule`
- **Features**:
  - View all course schedules
  - Create new schedules
  - Edit existing schedules
  - Delete schedules
  - Filter by semester and academic year
  - View schedule details:
    - Course information
    - Assigned doctor
    - Day of week
    - Time slots
    - Room location
    - Capacity and enrollment
  - Support for weekly schedules with multiple sessions
  - Support for multiple courses per schedule
  - Conflict detection:
    - Time slot conflicts
    - Room conflicts
    - Doctor availability conflicts

**Schedule Creation Features**:
- Select course(s)
- Assign doctor
- Set semester and academic year
- Set section number
- Configure time slots:
  - Single day schedule
  - Weekly schedule (multiple days)
- Set room location
- Set capacity
- Set status (scheduled, published, ongoing, completed, cancelled)

### ✅ Enrollment Management
- **Location**: `/it/enrollments`
- **Features**:
  - View all enrollment requests
  - Filter by status (pending, approved, rejected)
  - View request details:
    - Student information
    - Course and section
    - Request date
    - Status
  - Approve individual requests
  - Approve all pending requests (bulk action)
  - Reject requests with reason
  - See pending requests count badge

**Enrollment Approval Process**:
1. Go to Enrollments page
2. View pending requests
3. Review student and course details
4. Click "Approve" or "Reject"
5. If rejecting, provide reason
6. Student receives notification
7. Enrollment status updated

### 📚 Course Management
- **Location**: `/it/course`
- **Features**:
  - View all courses
  - Create new courses
  - Edit existing courses
  - Delete courses
  - Set course details:
    - Course code (unique)
    - Course name
    - Description
    - Credit hours
    - Department
  - View course prerequisites
  - Add/remove prerequisites
  - View assigned doctors
  - See sections for each course

**Course Creation Process**:
1. Go to Course page
2. Click "Create Course"
3. Enter course code (must be unique)
4. Enter course name
5. Add description
6. Set credit hours
7. Select department
8. Submit
9. Course available for schedule creation

### 📋 System Logs
- **Location**: `/it/logs`
- **Features**:
  - View audit logs
  - Filter logs by:
    - User
    - Action type
    - Date range
    - Entity type
  - See system activity:
    - User actions
    - Enrollment requests
    - Assignment submissions
    - Grade updates
    - Schedule changes
  - Export logs (if implemented)
  - Search logs

### 🔔 Send Notifications
- **Location**: `/it/send-notification`
- **Features**:
  - Send notifications to:
    - All students
    - All doctors
    - All IT officers
    - All admins
    - Specific users
  - Choose notification type
  - Add title and message
  - Bulk notification support

---

## Workflow Examples

### Creating a Schedule

1. Go to Schedule page (`/it/schedule`)
2. Click "Create Schedule"
3. Select course
4. Assign doctor
5. Set semester and year
6. Configure time slots:
   - **For single day**: Set day, start time, end time
   - **For weekly**: Add multiple days with sessions
7. Set room and capacity
8. Set status to "published"
9. Submit
10. Schedule available for student enrollment

**Schedule Creation Tips**:
- Check for conflicts before creating
- Set appropriate capacity
- Use weekly schedules for recurring classes
- Set status to "published" to make it available for enrollment
- Verify room availability

### Approving Enrollment Requests

1. Go to Enrollments page (`/it/enrollments`)
2. View pending requests (badge shows count)
3. Review each request:
   - Check student eligibility
   - Verify prerequisites
   - Check schedule conflicts
   - Verify capacity
4. Click "Approve" or "Reject"
5. If rejecting, enter reason
6. Student notified automatically
7. If approved, enrollment created

**Approval Best Practices**:
- Review prerequisites carefully
- Check for schedule conflicts
- Verify capacity availability
- Provide clear rejection reasons
- Process requests in a timely manner

### Creating a Course

1. Go to Course page (`/it/course`)
2. Click "Create Course"
3. Enter unique course code
4. Enter course name
5. Add description and details
6. Set credit hours
7. Select department
8. Submit
9. Course available for schedules

**Course Creation Tips**:
- Use consistent course code format
- Provide clear course descriptions
- Set appropriate credit hours
- Assign to correct department
- Add prerequisites if needed

### Managing Prerequisites

1. Go to Course page (`/it/course`)
2. Select course
3. View existing prerequisites
4. Add prerequisites as needed
5. Remove prerequisites if necessary
6. Save changes

### Bulk Approval of Enrollments

1. Go to Enrollments page (`/it/enrollments`)
2. Review pending requests
3. Click "Approve All" if all requests are valid
4. Confirm bulk approval
5. All students notified automatically

**Note**: Use bulk approval carefully. Review requests before approving all.

---

## Getting Started

### First Time Login

1. **Login**: Use your IT officer account credentials at `/auth/login`
2. **Dashboard**: View system statistics at `/it/dashboard`
3. **Enrollments**: Check pending enrollment requests at `/it/enrollments`
4. **Schedule**: Review existing schedules at `/it/schedule`
5. **Courses**: View course catalog at `/it/course`

### Daily Usage

1. **Check Dashboard**: Start your day by checking the dashboard
2. **Review Enrollments**: Process pending enrollment requests
3. **Manage Schedules**: Create or update schedules as needed
4. **Monitor Logs**: Check system activity logs
5. **Send Notifications**: Send important announcements

### Weekly Tasks

1. **Process Enrollments**: Review and approve/reject all pending requests
2. **Manage Schedules**: Create schedules for upcoming semesters
3. **Update Courses**: Add new courses or update existing ones
4. **Review Logs**: Monitor system activity
5. **Communicate**: Send notifications to users as needed

---

## Common Issues

### Cannot Create Schedule

**Possible Causes**:
- Course doesn't exist
- Doctor not assigned
- Conflicts detected (time, room, doctor)
- Missing required fields

**Solutions**:
1. Verify course exists
2. Check doctor assignment
3. Resolve conflicts (time, room, doctor)
4. Ensure all required fields are filled
5. Check conflict detection messages

### Cannot Approve Enrollment

**Possible Causes**:
- Prerequisites not met
- Schedule conflicts
- Capacity exceeded
- Student already enrolled

**Solutions**:
1. Verify prerequisites completion
2. Check for schedule conflicts
3. Verify capacity availability
4. Check existing enrollments
5. Review student eligibility

### Cannot Create Course

**Possible Causes**:
- Course code already exists
- Missing required fields
- Invalid data format

**Solutions**:
1. Use unique course code
2. Fill all required fields
3. Verify data format
4. Check for duplicate codes

### Schedule Conflicts Detected

**Possible Causes**:
- Time slot overlap
- Room double-booking
- Doctor availability conflict

**Solutions**:
1. Review conflict details
2. Adjust time slots
3. Change room assignment
4. Assign different doctor
5. Resolve all conflicts before saving

### Enrollment Request Issues

**Possible Causes**:
- Student prerequisites incomplete
- Schedule full
- Multiple enrollment requests
- System validation errors

**Solutions**:
1. Verify prerequisites
2. Check schedule capacity
3. Review student's existing requests
4. Check system validation messages

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
- View system statistics

### Notifications System

- Send notifications to all user types
- Receive system notifications
- View notification history
- Mark notifications as read
- See unread notification count
- Filter notifications by type

---

## Important Notes

- All actions are logged in the audit log
- Notifications are sent automatically when enrollments are approved/rejected
- Schedules support both single-day and weekly formats
- Conflict detection prevents scheduling issues
- Enrollment requests require your approval
- Students can only enroll in one schedule per semester
- Course codes must be unique
- Prerequisites are enforced during enrollment
- Bulk operations are available for efficiency

---

## Best Practices

### Schedule Management

- Create schedules well in advance
- Check for conflicts before saving
- Set appropriate capacity limits
- Use weekly schedules for recurring classes
- Keep schedules organized by semester

### Enrollment Processing

- Process requests in a timely manner
- Review prerequisites carefully
- Check for conflicts before approving
- Provide clear rejection reasons
- Use bulk approval when appropriate

### Course Management

- Use consistent course code formats
- Provide clear course descriptions
- Set appropriate credit hours
- Manage prerequisites effectively
- Keep course catalog updated

### System Monitoring

- Review logs regularly
- Monitor system activity
- Check for errors or issues
- Track enrollment trends
- Maintain system health

### Communication

- Send important announcements
- Notify users of schedule changes
- Provide clear instructions
- Respond to user inquiries
- Keep users informed

---

## Conflict Detection

The system automatically detects conflicts when creating schedules:

### Time Slot Conflicts
- Prevents overlapping class times
- Checks student schedules
- Validates doctor availability

### Room Conflicts
- Prevents double-booking of rooms
- Checks room availability
- Validates room capacity

### Doctor Availability Conflicts
- Prevents doctor schedule overlaps
- Checks doctor assignments
- Validates availability

**Resolution**:
- Review conflict details
- Adjust schedule parameters
- Resolve all conflicts before saving

---

## Support

For technical support or questions, contact your system administrator.

**Common Contact Points**:
- Administrator: For system configuration and user management
- System Support: For technical issues
- Database Admin: For database-related issues

---

**Last Updated**: December 2024  
**System Version**: 1.0
