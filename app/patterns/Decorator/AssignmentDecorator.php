<?php
namespace patterns\Decorator;

require_once __DIR__ . '/ModelDecorator.php'; // Ensure base class is loaded

/**
 * Assignment decorator with additional formatting
 * Decorator Pattern - Structural
 */
class AssignmentDecorator extends ModelDecorator
{
    public function format(): string
    {
        $title = $this->data['title'] ?? 'Untitled Assignment';
        $courseCode = $this->data['course_code'] ?? 'N/A';
        $dueDate = $this->data['due_date'] ?? 'TBA';
        $points = $this->data['max_points'] ?? 0;
        
        return "{$title} - {$courseCode} | Due: {$dueDate} | {$points} points";
    }

    public function getStatusBadge(): string
    {
        $dueDate = $this->data['due_date'] ?? null;
        if (!$dueDate) {
            return '<span class="badge badge-info">Draft</span>';
        }
        
        $dueTimestamp = strtotime($dueDate);
        $now = time();
        
        if ($dueTimestamp < $now) {
            return '<span class="badge badge-success">Completed</span>';
        } elseif ($dueTimestamp < ($now + 86400)) {
            return '<span class="badge badge-warning">Due Soon</span>';
        } else {
            return '<span class="badge badge-primary">Active</span>';
        }
    }
}

