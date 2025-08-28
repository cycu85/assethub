<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250828072614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aparatura_pomiarowa_equipment (id INT AUTO_INCREMENT NOT NULL, inventory_number VARCHAR(100) NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, equipment_type VARCHAR(100) NOT NULL, manufacturer VARCHAR(255) DEFAULT NULL, model VARCHAR(255) DEFAULT NULL, serial_number VARCHAR(100) DEFAULT NULL, manufacturing_date DATE DEFAULT NULL, purchase_date DATE DEFAULT NULL, purchase_price NUMERIC(10, 2) DEFAULT NULL, supplier VARCHAR(255) DEFAULT NULL, invoice_number VARCHAR(100) DEFAULT NULL, projekt VARCHAR(255) DEFAULT NULL, warranty_expiry DATE DEFAULT NULL, next_review_date DATE DEFAULT NULL, review_interval_months INT DEFAULT NULL, status VARCHAR(50) NOT NULL, assigned_date DATE DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, custom_fields JSON DEFAULT NULL, attachments JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, assigned_to_id INT DEFAULT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_E80F9138964C83FF (inventory_number), INDEX IDX_E80F9138F4BD7827 (assigned_to_id), INDEX IDX_E80F9138B03A8386 (created_by_id), INDEX IDX_E80F9138896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aparatura_pomiarowa_equipment_set (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, set_type VARCHAR(100) DEFAULT NULL, status VARCHAR(50) NOT NULL, assigned_date DATE DEFAULT NULL, next_review_date DATE DEFAULT NULL, review_interval_months INT DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, custom_fields JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, attachments JSON DEFAULT NULL, assigned_to_id INT DEFAULT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_4B78482AF4BD7827 (assigned_to_id), INDEX IDX_4B78482AB03A8386 (created_by_id), INDEX IDX_4B78482A896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aparatura_pomiarowa_equipment_set_items (aparatura_pomiarowa_equipment_set_id INT NOT NULL, aparatura_pomiarowa_equipment_id INT NOT NULL, INDEX IDX_77D6DA5B35E53A4E (aparatura_pomiarowa_equipment_set_id), INDEX IDX_77D6DA5BB4DCD73A (aparatura_pomiarowa_equipment_id), PRIMARY KEY (aparatura_pomiarowa_equipment_set_id, aparatura_pomiarowa_equipment_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aparatura_pomiarowa_review (id INT AUTO_INCREMENT NOT NULL, review_number VARCHAR(100) NOT NULL, status VARCHAR(50) NOT NULL, review_type VARCHAR(100) DEFAULT NULL, planned_date DATE NOT NULL, sent_date DATE DEFAULT NULL, completed_date DATE DEFAULT NULL, next_review_date DATE DEFAULT NULL, review_company VARCHAR(255) DEFAULT NULL, certificate_number VARCHAR(255) DEFAULT NULL, result VARCHAR(100) DEFAULT NULL, findings LONGTEXT DEFAULT NULL, recommendations LONGTEXT DEFAULT NULL, cost NUMERIC(8, 2) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, selected_equipment_ids JSON DEFAULT NULL, attachments JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, equipment_id INT DEFAULT NULL, equipment_set_id INT DEFAULT NULL, prepared_by_id INT NOT NULL, sent_by_id INT DEFAULT NULL, completed_by_id INT DEFAULT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_3AA613271CE65894 (review_number), INDEX IDX_3AA61327517FE9FE (equipment_id), INDEX IDX_3AA61327B7757258 (equipment_set_id), INDEX IDX_3AA61327393065A9 (prepared_by_id), INDEX IDX_3AA61327A45BB98C (sent_by_id), INDEX IDX_3AA6132785ECDE76 (completed_by_id), INDEX IDX_3AA61327B03A8386 (created_by_id), INDEX IDX_3AA61327896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aparatura_pomiarowa_review_equipment (id INT AUTO_INCREMENT NOT NULL, equipment_status_at_review VARCHAR(100) DEFAULT NULL, equipment_name_at_review VARCHAR(255) DEFAULT NULL, equipment_inventory_number_at_review VARCHAR(100) DEFAULT NULL, equipment_type_at_review VARCHAR(100) DEFAULT NULL, equipment_manufacturer_at_review VARCHAR(255) DEFAULT NULL, equipment_model_at_review VARCHAR(255) DEFAULT NULL, equipment_serial_number_at_review VARCHAR(100) DEFAULT NULL, equipment_next_review_date_at_review DATE DEFAULT NULL, individual_result VARCHAR(50) DEFAULT NULL, individual_findings LONGTEXT DEFAULT NULL, individual_recommendations LONGTEXT DEFAULT NULL, individual_next_review_date DATE DEFAULT NULL, was_in_set_at_review TINYINT(1) NOT NULL, set_name_at_review VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, review_id INT NOT NULL, equipment_id INT NOT NULL, INDEX IDX_C65906943E2E969B (review_id), INDEX IDX_C6590694517FE9FE (equipment_id), UNIQUE INDEX unique_review_equipment (review_id, equipment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE aparatura_pomiarowa_transfer (id INT AUTO_INCREMENT NOT NULL, transfer_number VARCHAR(100) NOT NULL, status VARCHAR(50) NOT NULL, transfer_date DATE NOT NULL, return_date DATE DEFAULT NULL, purpose LONGTEXT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, conditions LONGTEXT DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, selected_equipment_ids JSON DEFAULT NULL, protocol_scan_filename VARCHAR(255) DEFAULT NULL, protocol_uploaded_at DATETIME DEFAULT NULL, return_protocol_filename VARCHAR(255) DEFAULT NULL, return_protocol_uploaded_at DATETIME DEFAULT NULL, return_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, equipment_id INT DEFAULT NULL, equipment_set_id INT DEFAULT NULL, recipient_id INT NOT NULL, handed_by_id INT NOT NULL, returned_by_id INT DEFAULT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_1950D0E0F3834267 (transfer_number), INDEX IDX_1950D0E0517FE9FE (equipment_id), INDEX IDX_1950D0E0B7757258 (equipment_set_id), INDEX IDX_1950D0E0E92F8F78 (recipient_id), INDEX IDX_1950D0E07C9FDA18 (handed_by_id), INDEX IDX_1950D0E071AD87D9 (returned_by_id), INDEX IDX_1950D0E0B03A8386 (created_by_id), INDEX IDX_1950D0E0896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment ADD CONSTRAINT FK_E80F9138F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment ADD CONSTRAINT FK_E80F9138B03A8386 FOREIGN KEY (created_by_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment ADD CONSTRAINT FK_E80F9138896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set ADD CONSTRAINT FK_4B78482AF4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set ADD CONSTRAINT FK_4B78482AB03A8386 FOREIGN KEY (created_by_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set ADD CONSTRAINT FK_4B78482A896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set_items ADD CONSTRAINT FK_77D6DA5B35E53A4E FOREIGN KEY (aparatura_pomiarowa_equipment_set_id) REFERENCES aparatura_pomiarowa_equipment_set (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set_items ADD CONSTRAINT FK_77D6DA5BB4DCD73A FOREIGN KEY (aparatura_pomiarowa_equipment_id) REFERENCES aparatura_pomiarowa_equipment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review ADD CONSTRAINT FK_3AA61327517FE9FE FOREIGN KEY (equipment_id) REFERENCES aparatura_pomiarowa_equipment (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review ADD CONSTRAINT FK_3AA61327B7757258 FOREIGN KEY (equipment_set_id) REFERENCES aparatura_pomiarowa_equipment_set (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review ADD CONSTRAINT FK_3AA61327393065A9 FOREIGN KEY (prepared_by_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review ADD CONSTRAINT FK_3AA61327A45BB98C FOREIGN KEY (sent_by_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review ADD CONSTRAINT FK_3AA6132785ECDE76 FOREIGN KEY (completed_by_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review ADD CONSTRAINT FK_3AA61327B03A8386 FOREIGN KEY (created_by_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review ADD CONSTRAINT FK_3AA61327896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review_equipment ADD CONSTRAINT FK_C65906943E2E969B FOREIGN KEY (review_id) REFERENCES aparatura_pomiarowa_review (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review_equipment ADD CONSTRAINT FK_C6590694517FE9FE FOREIGN KEY (equipment_id) REFERENCES aparatura_pomiarowa_equipment (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer ADD CONSTRAINT FK_1950D0E0517FE9FE FOREIGN KEY (equipment_id) REFERENCES aparatura_pomiarowa_equipment (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer ADD CONSTRAINT FK_1950D0E0B7757258 FOREIGN KEY (equipment_set_id) REFERENCES aparatura_pomiarowa_equipment_set (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer ADD CONSTRAINT FK_1950D0E0E92F8F78 FOREIGN KEY (recipient_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer ADD CONSTRAINT FK_1950D0E07C9FDA18 FOREIGN KEY (handed_by_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer ADD CONSTRAINT FK_1950D0E071AD87D9 FOREIGN KEY (returned_by_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer ADD CONSTRAINT FK_1950D0E0B03A8386 FOREIGN KEY (created_by_id) REFERENCES `users` (id)');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer ADD CONSTRAINT FK_1950D0E0896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `users` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment DROP FOREIGN KEY FK_E80F9138F4BD7827');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment DROP FOREIGN KEY FK_E80F9138B03A8386');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment DROP FOREIGN KEY FK_E80F9138896DBBDE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set DROP FOREIGN KEY FK_4B78482AF4BD7827');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set DROP FOREIGN KEY FK_4B78482AB03A8386');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set DROP FOREIGN KEY FK_4B78482A896DBBDE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set_items DROP FOREIGN KEY FK_77D6DA5B35E53A4E');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set_items DROP FOREIGN KEY FK_77D6DA5BB4DCD73A');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review DROP FOREIGN KEY FK_3AA61327517FE9FE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review DROP FOREIGN KEY FK_3AA61327B7757258');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review DROP FOREIGN KEY FK_3AA61327393065A9');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review DROP FOREIGN KEY FK_3AA61327A45BB98C');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review DROP FOREIGN KEY FK_3AA6132785ECDE76');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review DROP FOREIGN KEY FK_3AA61327B03A8386');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review DROP FOREIGN KEY FK_3AA61327896DBBDE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review_equipment DROP FOREIGN KEY FK_C65906943E2E969B');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review_equipment DROP FOREIGN KEY FK_C6590694517FE9FE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer DROP FOREIGN KEY FK_1950D0E0517FE9FE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer DROP FOREIGN KEY FK_1950D0E0B7757258');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer DROP FOREIGN KEY FK_1950D0E0E92F8F78');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer DROP FOREIGN KEY FK_1950D0E07C9FDA18');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer DROP FOREIGN KEY FK_1950D0E071AD87D9');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer DROP FOREIGN KEY FK_1950D0E0B03A8386');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer DROP FOREIGN KEY FK_1950D0E0896DBBDE');
        $this->addSql('DROP TABLE aparatura_pomiarowa_equipment');
        $this->addSql('DROP TABLE aparatura_pomiarowa_equipment_set');
        $this->addSql('DROP TABLE aparatura_pomiarowa_equipment_set_items');
        $this->addSql('DROP TABLE aparatura_pomiarowa_review');
        $this->addSql('DROP TABLE aparatura_pomiarowa_review_equipment');
        $this->addSql('DROP TABLE aparatura_pomiarowa_transfer');
    }
}
