<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250821061209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dodanie tabeli asekuracyjny_review_equipment dla sztywnego przypisania elementów do przeglądów';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE asekuracyjny_review_equipment (id INT AUTO_INCREMENT NOT NULL, equipment_status_at_review VARCHAR(100) DEFAULT NULL, equipment_name_at_review VARCHAR(255) DEFAULT NULL, equipment_inventory_number_at_review VARCHAR(100) DEFAULT NULL, equipment_type_at_review VARCHAR(100) DEFAULT NULL, equipment_manufacturer_at_review VARCHAR(255) DEFAULT NULL, equipment_model_at_review VARCHAR(255) DEFAULT NULL, equipment_serial_number_at_review VARCHAR(100) DEFAULT NULL, equipment_next_review_date_at_review DATE DEFAULT NULL, individual_result VARCHAR(50) DEFAULT NULL, individual_findings LONGTEXT DEFAULT NULL, individual_recommendations LONGTEXT DEFAULT NULL, individual_next_review_date DATE DEFAULT NULL, was_in_set_at_review TINYINT(1) NOT NULL, set_name_at_review VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, review_id INT NOT NULL, equipment_id INT NOT NULL, INDEX IDX_E3B180153E2E969B (review_id), INDEX IDX_E3B18015517FE9FE (equipment_id), UNIQUE INDEX unique_review_equipment (review_id, equipment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE asekuracyjny_review_equipment ADD CONSTRAINT FK_E3B180153E2E969B FOREIGN KEY (review_id) REFERENCES asekuracyjny_review (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE asekuracyjny_review_equipment ADD CONSTRAINT FK_E3B18015517FE9FE FOREIGN KEY (equipment_id) REFERENCES asekuracyjny_equipment (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE asekuracyjny_review_equipment DROP FOREIGN KEY FK_E3B180153E2E969B');
        $this->addSql('ALTER TABLE asekuracyjny_review_equipment DROP FOREIGN KEY FK_E3B18015517FE9FE');
        $this->addSql('DROP TABLE asekuracyjny_review_equipment');
    }
}
