<?php

namespace App\Entity;

use App\Repository\EmailHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailHistoryRepository::class)]
#[ORM\Table(name: 'email_history')]
#[ORM\Index(columns: ['sent_at'], name: 'idx_email_history_sent_at')]
#[ORM\Index(columns: ['recipient_email'], name: 'idx_email_history_recipient')]
#[ORM\Index(columns: ['status'], name: 'idx_email_history_status')]
class EmailHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $recipient_email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $recipient_name = null;

    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $body_text = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $body_html = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sender_email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sender_name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $sent_at = null;

    #[ORM\Column(length: 50, options: ['default' => 'sent'])]
    private string $status = 'sent';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error_message = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $email_type = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $sent_by = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $message_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipientEmail(): ?string
    {
        return $this->recipient_email;
    }

    public function setRecipientEmail(string $recipient_email): static
    {
        $this->recipient_email = $recipient_email;
        return $this;
    }

    public function getRecipientName(): ?string
    {
        return $this->recipient_name;
    }

    public function setRecipientName(?string $recipient_name): static
    {
        $this->recipient_name = $recipient_name;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getBodyText(): ?string
    {
        return $this->body_text;
    }

    public function setBodyText(?string $body_text): static
    {
        $this->body_text = $body_text;
        return $this;
    }

    public function getBodyHtml(): ?string
    {
        return $this->body_html;
    }

    public function setBodyHtml(?string $body_html): static
    {
        $this->body_html = $body_html;
        return $this;
    }

    public function getSenderEmail(): ?string
    {
        return $this->sender_email;
    }

    public function setSenderEmail(?string $sender_email): static
    {
        $this->sender_email = $sender_email;
        return $this;
    }

    public function getSenderName(): ?string
    {
        return $this->sender_name;
    }

    public function setSenderName(?string $sender_name): static
    {
        $this->sender_name = $sender_name;
        return $this;
    }

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sent_at;
    }

    public function setSentAt(\DateTimeInterface $sent_at): static
    {
        $this->sent_at = $sent_at;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->error_message;
    }

    public function setErrorMessage(?string $error_message): static
    {
        $this->error_message = $error_message;
        return $this;
    }

    public function getEmailType(): ?string
    {
        return $this->email_type;
    }

    public function setEmailType(?string $email_type): static
    {
        $this->email_type = $email_type;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getSentBy(): ?User
    {
        return $this->sent_by;
    }

    public function setSentBy(?User $sent_by): static
    {
        $this->sent_by = $sent_by;
        return $this;
    }

    public function getMessageId(): ?string
    {
        return $this->message_id;
    }

    public function setMessageId(?string $message_id): static
    {
        $this->message_id = $message_id;
        return $this;
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'sent';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            'sent' => 'Wysłany',
            'failed' => 'Błąd',
            'queued' => 'W kolejce',
            'delivered' => 'Dostarczony',
            'bounced' => 'Odrzucony',
            default => 'Nieznany'
        };
    }
}