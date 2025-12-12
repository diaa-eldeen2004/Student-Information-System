<?php
namespace patterns\Factory;

use models\ItOfficer;
use models\Section;
use models\Course;
use models\Doctor;
use models\EnrollmentRequest;
use models\AuditLog;
use models\Notification;
use models\Student;
use models\Assignment;
use models\Attendance;

/**
 * Factory Method Pattern - Creational
 * Factory for creating model instances
 */
class ModelFactory
{
    private static array $instances = [];

    /**
     * Create a model instance based on type
     */
    public static function create(string $modelType): object
    {
        // Singleton pattern combined with Factory
        if (isset(self::$instances[$modelType])) {
            return self::$instances[$modelType];
        }

        $model = match($modelType) {
            'ItOfficer' => new ItOfficer(),
            'Section' => new Section(),
            'Course' => new Course(),
            'Doctor' => new Doctor(),
            'EnrollmentRequest' => new EnrollmentRequest(),
            'AuditLog' => new AuditLog(),
            'Notification' => new Notification(),
            'Student' => new Student(),
            'Assignment' => new Assignment(),
            'Attendance' => new Attendance(),
            default => throw new \InvalidArgumentException("Unknown model type: {$modelType}")
        };

        self::$instances[$modelType] = $model;
        return $model;
    }

    /**
     * Reset instances (useful for testing)
     */
    public static function reset(): void
    {
        self::$instances = [];
    }
}

