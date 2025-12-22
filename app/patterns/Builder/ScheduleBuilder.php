<?php
namespace patterns\Builder;

use models\Schedule;

/**
 * Builder Pattern - Creational
 * Builds Schedule objects step by step
 */
class ScheduleBuilder
{
    private array $data = [];

    public function setCourse(int $courseId): self
    {
        $this->data['course_id'] = $courseId;
        return $this;
    }

    public function setDoctor(int $doctorId): self
    {
        $this->data['doctor_id'] = $doctorId;
        return $this;
    }

    public function setSectionNumber(string $sectionNumber): self
    {
        $this->data['section_number'] = $sectionNumber;
        return $this;
    }

    public function setSemester(string $semester): self
    {
        $this->data['semester'] = $semester;
        return $this;
    }

    public function setAcademicYear(string $academicYear): self
    {
        $this->data['academic_year'] = $academicYear;
        return $this;
    }

    public function setRoom(?string $room): self
    {
        $this->data['room'] = $room;
        return $this;
    }

    public function setTimeSlot(string $dayOfWeek, string $startTime, string $endTime): self
    {
        $this->data['day_of_week'] = $dayOfWeek;
        $this->data['start_time'] = $startTime;
        $this->data['end_time'] = $endTime;
        $this->data['time_slot'] = "{$dayOfWeek} {$startTime}-{$endTime}";
        return $this;
    }

    public function setCapacity(int $capacity): self
    {
        $this->data['capacity'] = $capacity;
        return $this;
    }

    public function setSessionType(string $sessionType): self
    {
        $this->data['session_type'] = $sessionType;
        return $this;
    }

    public function build(): array
    {
        // Validate required fields
        $required = ['course_id', 'doctor_id', 'section_number', 'semester', 'academic_year', 'day_of_week', 'start_time', 'end_time'];
        foreach ($required as $field) {
            if (!isset($this->data[$field])) {
                throw new \RuntimeException("Required field '{$field}' is missing");
            }
        }

        // Set defaults
        $this->data['capacity'] = $this->data['capacity'] ?? 30;
        $this->data['room'] = $this->data['room'] ?? null;

        return $this->data;
    }

    public function create(Schedule $scheduleModel): bool
    {
        $data = $this->build();
        return $scheduleModel->create($data);
    }
}

