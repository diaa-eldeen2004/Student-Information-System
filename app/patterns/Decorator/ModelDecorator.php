<?php
namespace patterns\Decorator;

/**
 * Decorator Pattern - Structural
 * Base decorator interface
 */
interface ModelDecoratorInterface
{
    public function getData(): array;
    public function format(): string;
}

/**
 * Base model decorator
 */
abstract class ModelDecorator implements ModelDecoratorInterface
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    abstract public function format(): string;
}

/**
 * Section decorator with additional formatting
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

/**
 * Enrollment Request decorator
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

