<?php
namespace patterns\Observer;

use models\Notification;

/**
 * Observer Pattern - Behavioral
 * Concrete observer that sends notifications
 */
class NotificationObserver implements Observer
{
    private Notification $notificationModel;

    public function __construct(Notification $notificationModel)
    {
        $this->notificationModel = $notificationModel;
    }

    public function update(string $event, array $data): void
    {
        switch ($event) {
            case 'enrollment.approved':
                $this->handleEnrollmentApproved($data);
                break;
            case 'enrollment.rejected':
                $this->handleEnrollmentRejected($data);
                break;
            case 'section.created':
                $this->handleSectionCreated($data);
                break;
        }
    }

    private function handleEnrollmentApproved(array $data): void
    {
        if (isset($data['user_id'])) {
            $this->notificationModel->create([
                'user_id' => $data['user_id'],
                'title' => 'Enrollment Approved',
                'message' => $data['message'] ?? 'Your enrollment request has been approved.',
                'type' => 'success',
                'related_id' => $data['request_id'] ?? null,
                'related_type' => 'enrollment_request',
            ]);
        }
    }

    private function handleEnrollmentRejected(array $data): void
    {
        if (isset($data['user_id'])) {
            $this->notificationModel->create([
                'user_id' => $data['user_id'],
                'title' => 'Enrollment Rejected',
                'message' => $data['message'] ?? 'Your enrollment request has been rejected.',
                'type' => 'warning',
                'related_id' => $data['request_id'] ?? null,
                'related_type' => 'enrollment_request',
            ]);
        }
    }

    private function handleSectionCreated(array $data): void
    {
        if (isset($data['user_id'])) {
            $this->notificationModel->create([
                'user_id' => $data['user_id'],
                'title' => 'New Section Assigned',
                'message' => $data['message'] ?? 'You have been assigned to a new section.',
                'type' => 'info',
                'related_id' => $data['section_id'] ?? null,
                'related_type' => 'section',
            ]);
        }
    }
}

