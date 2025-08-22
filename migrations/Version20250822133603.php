<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250822133603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email_history (id INT AUTO_INCREMENT NOT NULL, recipient_email VARCHAR(255) NOT NULL, recipient_name VARCHAR(255) DEFAULT NULL, subject VARCHAR(255) NOT NULL, body_text LONGTEXT DEFAULT NULL, body_html LONGTEXT DEFAULT NULL, sender_email VARCHAR(255) DEFAULT NULL, sender_name VARCHAR(255) DEFAULT NULL, sent_at DATETIME NOT NULL, status VARCHAR(50) DEFAULT \'sent\' NOT NULL, error_message LONGTEXT DEFAULT NULL, email_type VARCHAR(100) DEFAULT NULL, metadata JSON DEFAULT NULL, message_id VARCHAR(255) DEFAULT NULL, sent_by_id INT DEFAULT NULL, INDEX IDX_9A7A1884A45BB98C (sent_by_id), INDEX idx_email_history_sent_at (sent_at), INDEX idx_email_history_recipient (recipient_email), INDEX idx_email_history_status (status), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE email_history ADD CONSTRAINT FK_9A7A1884A45BB98C FOREIGN KEY (sent_by_id) REFERENCES `users` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE asekuracyjny_equipment DROP projekt');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_history DROP FOREIGN KEY FK_9A7A1884A45BB98C');
        $this->addSql('DROP TABLE email_history');
        $this->addSql('ALTER TABLE asekuracyjny_equipment ADD projekt VARCHAR(255) DEFAULT NULL');
    }
}
