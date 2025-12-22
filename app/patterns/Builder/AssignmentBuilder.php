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

    public function setFile(string $filePath, string $fileName, ?int $fileSize = null): self
    {
        $this->data['file_path'] = $filePath;
        $this->data['file_name'] = $fileName;
        $this->data['file_size'] = $fileSize;
        return $this;
    }

    public function setVisibility(bool $isVisible, ?string $visibleUntil = null): self
    {
        $this->data['is_visible'] = $isVisible;
        $this->data['visible_until'] = $visibleUntil;
        return $this;
    }

    public function setSemester(string $semester, string $academicYear): self
    {
        $this->data['semester'] = $semester;
        $this->data['academic_year'] = $academicYear;
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
        $this->data['is_visible'] = $this->data['is_visible'] ?? 1;
        $this->data['file_path'] = $this->data['file_path'] ?? null;
        $this->data['file_name'] = $this->data['file_name'] ?? null;
        $this->data['file_size'] = $this->data['file_size'] ?? null;

        return $this->data;
    }
}

