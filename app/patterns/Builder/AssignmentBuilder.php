<?php
namespace patterns\Builder;

/**
 * Builder Pattern - Creational
 * Builds Assignment objects step by step
 */
class AssignmentBuilder
{
    private array $data = [];

    public function setCourse(int $courseId): self
    {
        $this->data['course_id'] = $courseId;
        return $this;
    }

    public function setSection(int $sectionId): self
    {
        $this->data['section_id'] = $sectionId;
        return $this;
    }

    public function setDoctor(int $doctorId): self
    {
        $this->data['doctor_id'] = $doctorId;
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->data['title'] = $title;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->data['description'] = $description;
        return $this;
    }

    public function setDueDate(string $dueDate): self
    {
        $this->data['due_date'] = $dueDate;
        return $this;
    }

    public function setMaxPoints(float $points): self
    {
        $this->data['max_points'] = $points;
        return $this;
    }

    public function setType(string $type): self
    {
        $this->data['assignment_type'] = $type;
        return $this;
    }

    public function build(): array
    {
        // Validate required fields
        $required = ['course_id', 'section_id', 'doctor_id', 'title', 'due_date'];
        foreach ($required as $field) {
            if (!isset($this->data[$field])) {
                throw new \RuntimeException("Required field '{$field}' is missing");
            }
        }

        // Set defaults
        $this->data['max_points'] = $this->data['max_points'] ?? 100;
        $this->data['assignment_type'] = $this->data['assignment_type'] ?? 'homework';
        $this->data['description'] = $this->data['description'] ?? null;

        return $this->data;
    }
}

