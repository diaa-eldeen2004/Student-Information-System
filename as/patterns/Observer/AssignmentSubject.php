<?php
namespace patterns\Observer;

/**
 * Observer Pattern - Behavioral
 * Concrete subject for assignment events
 */
class AssignmentSubject implements Subject
{
    private array $observers = [];

    public function attach(Observer $observer): void
    {
        $this->observers[] = $observer;
    }

    public function detach(Observer $observer): void
    {
        $key = array_search($observer, $this->observers, true);
        if ($key !== false) {
            unset($this->observers[$key]);
            $this->observers = array_values($this->observers);
        }
    }

    public function notify(string $event, array $data = []): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($event, $data);
        }
    }

    public function assignmentCreated(array $assignmentData): void
    {
        $this->notify('assignment.created', $assignmentData);
    }

    public function assignmentUpdated(array $assignmentData): void
    {
        $this->notify('assignment.updated', $assignmentData);
    }
}

