<?php

namespace App\EventSubscriber;

use App\Event\Equipment\EquipmentCreatedEvent;
use App\Event\Equipment\EquipmentAssignedEvent;
use App\Service\AuditService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EquipmentEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditService $auditService,
        private LoggerInterface $logger,
        private ?MailerInterface $mailer = null
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EquipmentCreatedEvent::NAME => [
                ['onEquipmentCreated', 10],
                ['checkHighValueEquipment', 5],
            ],
            EquipmentAssignedEvent::NAME => [
                ['onEquipmentAssigned', 10],
                ['notifyAssignee', 5],
                ['updateEquipmentStatistics', 0],
            ],
        ];
    }

    public function onEquipmentCreated(EquipmentCreatedEvent $event): void
    {
        $equipment = $event->getEquipment();
        $createdBy = $event->getCreatedBy();

        // Detailed audit logging
        $this->auditService->logUserAction($createdBy, 'equipment_created_event', [
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName(),
            'inventory_number' => $equipment->getInventoryNumber(),
            'serial_number' => $equipment->getSerialNumber(),
            'category' => $equipment->getCategory()?->getName(),
            'purchase_price' => $equipment->getPurchasePrice(),
            'context' => $event->getContext()
        ]);

        $this->logger->info('Equipment created via event system', [
            'equipment_id' => $equipment->getId(),
            'name' => $equipment->getName(),
            'created_by' => $createdBy->getUsername(),
            'category' => $equipment->getCategory()?->getName()
        ]);
    }

    public function checkHighValueEquipment(EquipmentCreatedEvent $event): void
    {
        $equipment = $event->getEquipment();
        $highValueThreshold = 10000.0; // 10,000 PLN

        if ($equipment->getPurchasePrice() && $equipment->getPurchasePrice() >= $highValueThreshold) {
            $this->auditService->logSecurityEvent('high_value_equipment_created', $event->getCreatedBy(), [
                'equipment_id' => $equipment->getId(),
                'equipment_name' => $equipment->getName(),
                'purchase_price' => $equipment->getPurchasePrice(),
                'threshold' => $highValueThreshold
            ]);

            $this->logger->warning('High-value equipment created', [
                'equipment_id' => $equipment->getId(),
                'name' => $equipment->getName(),
                'price' => $equipment->getPurchasePrice(),
                'created_by' => $event->getCreatedBy()->getUsername()
            ]);
        }
    }

    public function onEquipmentAssigned(EquipmentAssignedEvent $event): void
    {
        $equipment = $event->getEquipment();
        $assignedTo = $event->getAssignedTo();
        $assignedBy = $event->getAssignedBy();

        // Detailed audit logging
        $this->auditService->logUserAction($assignedBy, 'equipment_assigned_event', [
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName(),
            'assigned_to_id' => $assignedTo->getId(),
            'assigned_to_username' => $assignedTo->getUsername(),
            'previous_assignee_id' => $event->getPreviousAssignee()?->getId(),
            'notes' => $event->getNotes(),
            'context' => $event->getContext()
        ]);

        $this->logger->info('Equipment assigned via event system', [
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName(),
            'assigned_to' => $assignedTo->getUsername(),
            'assigned_by' => $assignedBy->getUsername(),
            'had_previous_assignee' => $event->getPreviousAssignee() !== null
        ]);
    }

    public function notifyAssignee(EquipmentAssignedEvent $event): void
    {
        if (!$this->mailer) {
            return; // Mailer not configured
        }

        $equipment = $event->getEquipment();
        $assignedTo = $event->getAssignedTo();
        $assignedBy = $event->getAssignedBy();

        try {
            $email = (new Email())
                ->from('noreply@assethub.local')
                ->to($assignedTo->getEmail())
                ->subject('Przypisano Ci nowy sprzęt - ' . $equipment->getName())
                ->html($this->getAssignmentEmailContent($equipment, $assignedTo, $assignedBy, $event->getNotes()));

            $this->mailer->send($email);

            $this->logger->info('Equipment assignment notification sent', [
                'equipment_id' => $equipment->getId(),
                'assigned_to_email' => $assignedTo->getEmail()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send equipment assignment notification', [
                'equipment_id' => $equipment->getId(),
                'assigned_to_email' => $assignedTo->getEmail(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateEquipmentStatistics(EquipmentAssignedEvent $event): void
    {
        // This could update cached statistics or trigger other background tasks
        $this->logger->debug('Equipment statistics update triggered', [
            'equipment_id' => $event->getEquipment()->getId(),
            'trigger' => 'equipment_assigned'
        ]);

        // Here you could:
        // - Update cached statistics
        // - Trigger reports generation  
        // - Update dashboard metrics
        // - Send notifications to managers
    }

    private function getAssignmentEmailContent($equipment, $assignedTo, $assignedBy, $notes): string
    {
        return sprintf(
            '<h1>Przypisano Ci nowy sprzęt</h1>
            <p>Witaj %s,</p>
            <p>Zostałeś wyznaczony jako użytkownik następującego sprzętu:</p>
            <ul>
                <li><strong>Nazwa:</strong> %s</li>
                <li><strong>Numer inwentarzowy:</strong> %s</li>
                <li><strong>Model:</strong> %s %s</li>
                <li><strong>Przypisane przez:</strong> %s</li>
                <li><strong>Data przypisania:</strong> %s</li>
            </ul>
            %s
            <p>Jeśli masz pytania dotyczące tego sprzętu, skontaktuj się z administratorem systemu.</p>
            <p>Zespół AssetHub</p>',
            htmlspecialchars($assignedTo->getFullName()),
            htmlspecialchars($equipment->getName()),
            htmlspecialchars($equipment->getInventoryNumber() ?? 'Brak'),
            htmlspecialchars($equipment->getManufacturer() ?? ''),
            htmlspecialchars($equipment->getModel() ?? ''),
            htmlspecialchars($assignedBy->getFullName()),
            (new \DateTime())->format('Y-m-d H:i:s'),
            $notes ? '<p><strong>Notatki:</strong> ' . htmlspecialchars($notes) . '</p>' : ''
        );
    }
}