<?php
namespace patterns\Decorator;

// Ensure base class is loaded
require_once __DIR__ . '/ModelDecorator.php';

/**
 * Enrollment Request decorator with additional formatting
 * Decorator Pattern - Structural
 */
class EnrollmentRequestDecorator extends ModelDecorator
{
    public function format(): string
    {
        $studentName = ($this->data['student_first_name'] ?? '') . ' ' . ($this->data['student_last_name'] ?? '');
        $courseCode = $this->data['course_code'] ?? 'N/A';
        $sectionNumber = $this->data['section_number'] ?? 'N/A';
        $status = ucfirst($this->data['status'] ?? 'pending');
        
        return "{$studentName} - {$courseCode} Section {$sectionNumber} [{$status}]";
    }

    public function getStatusBadge(): string
    {
        $status = $this->data['status'] ?? 'pending';
        $badges = [
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'approved' => '<span class="badge badge-success">Approved</span>',
            'rejected' => '<span class="badge badge-danger">Rejected</span>',
        ];
        
        return $badges[$status] ?? '<span class="badge">Unknown</span>';
    }
}

