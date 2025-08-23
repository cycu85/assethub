<?php

namespace App\AsekuracyjnySPM\EventSubscriber;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyReview;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet;
use App\Service\AuditService;
use App\Service\EmailService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsDoctrineListener(event: Events::postPersist)]
class AsekuracyjnyEventSubscriber
{
    public function __construct(
        private AuditService $auditService,
        private LoggerInterface $logger,
        private ?EmailService $emailService = null // Opcjonalnie zgodnie z wzorcem
    ) {}

    public function postPersist(PostPersistEventArgs $event): void
    {
        $entity = $event->getObject();

        // Obsługa utworzenia przeglądu
        if ($entity instanceof AsekuracyjnyReview) {
            $this->onReviewCreated($entity);
        }
    }

    private function onReviewCreated(AsekuracyjnyReview $review): void
    {
        // Audit
        $this->auditService->logUserAction($review->getCreatedBy(), 'asekuracja_review_created_event', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber(),
            'type' => $review->getEquipment() ? 'equipment' : 'equipment_set'
        ]);

        // Email notification dla przeglądów zestawów (jeśli EmailService dostępny)
        if ($this->emailService && $review->getEquipmentSet()) {
            $this->sendEquipmentSetReviewNotification($review);
        }
    }

    private function sendEquipmentSetReviewNotification(AsekuracyjnyReview $review): void
    {
        $equipmentSet = $review->getEquipmentSet();
        
        if (!$equipmentSet || !$equipmentSet->getAssignedTo()) {
            $this->logger->info('Review notification skipped - no assigned user', [
                'review_id' => $review->getId(),
                'equipment_set_id' => $equipmentSet?->getId()
            ]);
            return;
        }

        $assignedUser = $equipmentSet->getAssignedTo();
        
        if (!$assignedUser->getEmail()) {
            $this->logger->warning('Cannot send review notification - user has no email address', [
                'user_id' => $assignedUser->getId(),
                'username' => $assignedUser->getUsername(),
                'review_id' => $review->getId()
            ]);
            return;
        }

        // Pobierz elementy zestawu które będą w przeglądzie
        $equipmentList = [];
        $equipmentsToReview = $review->getSelectedEquipmentIds() 
            ? $equipmentSet->getEquipment()->filter(function($equipment) use ($review) {
                return in_array($equipment->getId(), $review->getSelectedEquipmentIds());
            })
            : $equipmentSet->getEquipment();

        foreach ($equipmentsToReview as $equipment) {
            $equipmentList[] = [
                'name' => $equipment->getName(),
                'inventory_number' => $equipment->getInventoryNumber(),
                'serial_number' => $equipment->getSerialNumber()
            ];
        }

        $reviewData = [
            'review_number' => $review->getReviewNumber(),
            'set_name' => $equipmentSet->getName(),
            'review_type' => $review->getReviewTypeDisplayName(),
            'planned_date' => $review->getPlannedDate()->format('d.m.Y'),
            'review_company' => $review->getReviewCompany() ?: 'Nie określono',
            'notes' => $review->getNotes(),
            'equipment_list' => $equipmentList,
            'equipment_set_id' => $equipmentSet->getId(),
            'review_id' => $review->getId()
        ];

        try {
            $success = $this->emailService->sendEquipmentSetReviewPreparedEmail(
                $assignedUser->getEmail(),
                $assignedUser->getFullName() ?: $assignedUser->getUsername(),
                $reviewData
            );

            if ($success) {
                $this->logger->info('Review notification email sent successfully', [
                    'review_id' => $review->getId(),
                    'equipment_set_id' => $equipmentSet->getId(),
                    'recipient_email' => $assignedUser->getEmail(),
                    'equipment_count' => count($equipmentList)
                ]);
            } else {
                $this->logger->warning('Failed to send review notification email', [
                    'review_id' => $review->getId(),
                    'equipment_set_id' => $equipmentSet->getId(),
                    'recipient_email' => $assignedUser->getEmail()
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception occurred while sending review notification email', [
                'review_id' => $review->getId(),
                'equipment_set_id' => $equipmentSet->getId(),
                'recipient_email' => $assignedUser->getEmail(),
                'error' => $e->getMessage()
            ]);
        }
    }
}