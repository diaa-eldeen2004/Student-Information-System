<?php
namespace patterns\Observer;

use models\AuditLog;

/**
 * Observer Pattern - Behavioral
 * Concrete observer that logs events to audit log
 */
class AuditLogObserver implements Observer
{
    private AuditLog $auditLogModel;

    public function __construct(AuditLog $auditLogModel)
    {
        $this->auditLogModel = $auditLogModel;
    }

    public function update(string $event, array $data): void
    {
        $userId = $data['user_id'] ?? null;
        $action = $this->mapEventToAction($event);
        $entityType = $data['entity_type'] ?? null;
        $entityId = $data['entity_id'] ?? null;
        $details = $data['details'] ?? json_encode($data);

        $this->auditLogModel->log($action, $userId, $entityType, $entityId, $details);
    }

    private function mapEventToAction(string $event): string
    {
        return match($event) {
            'enrollment.approved' => 'enrollment_approved',
            'enrollment.rejected' => 'enrollment_rejected',
            'section.created' => 'section_created',
            default => $event,
        };
    }
}

