<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notifications')]
#[ORM\Index(columns: ['user_id', 'is_read', 'created_at'], name: 'idx_user_notifications')]
class Notification
{
    public const TYPE_INFO = 'info';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_WARNING = 'warning';
    public const TYPE_ERROR = 'error';
    public const TYPE_MESSAGE = 'message';
    
    public const CATEGORY_SYSTEM = 'system';
    public const CATEGORY_EQUIPMENT = 'equipment';
    public const CATEGORY_REVIEW = 'review';
    public const CATEGORY_TRANSFER = 'transfer';
    public const CATEGORY_USER = 'user';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 50)]
    private ?string $category = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $data = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $actionUrl = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $actionText = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->type = self::TYPE_INFO;
        $this->category = self::CATEGORY_SYSTEM;
        $this->data = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function getActionUrl(): ?string
    {
        return $this->actionUrl;
    }

    public function setActionUrl(?string $actionUrl): static
    {
        $this->actionUrl = $actionUrl;
        return $this;
    }

    public function getActionText(): ?string
    {
        return $this->actionText;
    }

    public function setActionText(?string $actionText): static
    {
        $this->actionText = $actionText;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;
        if ($isRead && !$this->readAt) {
            $this->readAt = new \DateTimeImmutable();
        } elseif (!$isRead) {
            $this->readAt = null;
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;
        return $this;
    }

    public function markAsRead(): static
    {
        return $this->setIsRead(true);
    }

    public function markAsUnread(): static
    {
        return $this->setIsRead(false);
    }

    public function getTypeIcon(): string
    {
        return match ($this->type) {
            self::TYPE_SUCCESS => 'bx-check-circle',
            self::TYPE_WARNING => 'bx-error-circle',
            self::TYPE_ERROR => 'bx-x-circle',
            self::TYPE_MESSAGE => 'bx-message-square-dots',
            default => 'bx-info-circle'
        };
    }

    public function getTypeColor(): string
    {
        return match ($this->type) {
            self::TYPE_SUCCESS => 'success',
            self::TYPE_WARNING => 'warning',
            self::TYPE_ERROR => 'danger',
            self::TYPE_MESSAGE => 'info',
            default => 'primary'
        };
    }

    public function getCategoryIcon(): string
    {
        return match ($this->category) {
            self::CATEGORY_EQUIPMENT => 'ri-tools-line',
            self::CATEGORY_REVIEW => 'ri-clipboard-line',
            self::CATEGORY_TRANSFER => 'ri-arrow-left-right-line',
            self::CATEGORY_USER => 'ri-user-line',
            default => 'ri-notification-line'
        };
    }

    public function getTimeAgo(): string
    {
        $now = new \DateTimeImmutable();
        $interval = $now->diff($this->createdAt);

        if ($interval->d > 0) {
            return $interval->d . ' dni temu';
        }
        
        if ($interval->h > 0) {
            return $interval->h . ' godz. temu';
        }
        
        if ($interval->i > 0) {
            return $interval->i . ' min. temu';
        }
        
        return 'Teraz';
    }
}