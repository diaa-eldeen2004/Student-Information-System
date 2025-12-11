<?php
namespace patterns\Observer;

/**
 * Observer Pattern - Behavioral
 * Concrete subject for enrollment events
 */
class EnrollmentSubject implements Subject
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

    public function enrollmentApproved(array $enrollmentData): void
    {
        $this->notify('enrollment.approved', $enrollmentData);
    }

    public function enrollmentRejected(array $enrollmentData): void
    {
        $this->notify('enrollment.rejected', $enrollmentData);
    }

    public function sectionCreated(array $sectionData): void
    {
        $this->notify('section.created', $sectionData);
    }
}

