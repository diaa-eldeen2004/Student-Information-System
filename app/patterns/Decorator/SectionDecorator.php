<?php
namespace patterns\Decorator;

// Ensure base class is loaded
require_once __DIR__ . '/ModelDecorator.php';

/**
 * Section decorator with additional formatting
 * Decorator Pattern - Structural
 */
class SectionDecorator extends ModelDecorator
{
    public function format(): string
    {
        $courseCode = $this->data['course_code'] ?? 'N/A';
        $sectionNumber = $this->data['section_number'] ?? 'N/A';
        $timeSlot = $this->data['time_slot'] ?? 'TBA';
        $room = $this->data['room'] ?? 'TBA';
        
        return "{$courseCode} - Section {$sectionNumber} | {$timeSlot} | Room: {$room}";
    }

    public function getEnrollmentStatus(): string
    {
        $current = $this->data['current_enrollment'] ?? 0;
        $capacity = $this->data['capacity'] ?? 0;
        $percentage = $capacity > 0 ? round(($current / $capacity) * 100, 1) : 0;
        
        return "{$current}/{$capacity} ({$percentage}%)";
    }
}

