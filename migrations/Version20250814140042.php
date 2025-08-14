<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250814140042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE asekuracyjny_equipment DROP FOREIGN KEY `FK_7B311A72896DBBDE`');
        $this->addSql('ALTER TABLE asekuracyjny_equipment DROP FOREIGN KEY `FK_7B311A72B03A8386`');
        $this->addSql('ALTER TABLE asekuracyjny_equipment DROP FOREIGN KEY `FK_7B311A72F4BD7827`');
        $this->addSql('ALTER TABLE asekuracyjny_equipment_set DROP FOREIGN KEY `FK_7A72DD91896DBBDE`');
        $this->addSql('ALTER TABLE asekuracyjny_equipment_set DROP FOREIGN KEY `FK_7A72DD91B03A8386`');
        $this->addSql('ALTER TABLE asekuracyjny_equipment_set DROP FOREIGN KEY `FK_7A72DD91F4BD7827`');
        $this->addSql('ALTER TABLE asekuracyjny_equipment_set_items DROP FOREIGN KEY `FK_5429FE4E100DBCCF`');
        $this->addSql('ALTER TABLE asekuracyjny_equipment_set_items DROP FOREIGN KEY `FK_5429FE4ED0C78394`');
        $this->addSql('ALTER TABLE asekuracyjny_review DROP FOREIGN KEY `FK_6A22B80C393065A9`');
        $this->addSql('ALTER TABLE asekuracyjny_review DROP FOREIGN KEY `FK_6A22B80C517FE9FE`');
        $this->addSql('ALTER TABLE asekuracyjny_review DROP FOREIGN KEY `FK_6A22B80C85ECDE76`');
        $this->addSql('ALTER TABLE asekuracyjny_review DROP FOREIGN KEY `FK_6A22B80C896DBBDE`');
        $this->addSql('ALTER TABLE asekuracyjny_review DROP FOREIGN KEY `FK_6A22B80CA45BB98C`');
        $this->addSql('ALTER TABLE asekuracyjny_review DROP FOREIGN KEY `FK_6A22B80CB03A8386`');
        $this->addSql('ALTER TABLE asekuracyjny_review DROP FOREIGN KEY `FK_6A22B80CB7757258`');
        $this->addSql('ALTER TABLE asekuracyjny_transfer DROP FOREIGN KEY `FK_2E24076D517FE9FE`');
        $this->addSql('ALTER TABLE asekuracyjny_transfer DROP FOREIGN KEY `FK_2E24076D71AD87D9`');
        $this->addSql('ALTER TABLE asekuracyjny_transfer DROP FOREIGN KEY `FK_2E24076D7C9FDA18`');
        $this->addSql('ALTER TABLE asekuracyjny_transfer DROP FOREIGN KEY `FK_2E24076D896DBBDE`');
        $this->addSql('ALTER TABLE asekuracyjny_transfer DROP FOREIGN KEY `FK_2E24076DB03A8386`');
        $this->addSql('ALTER TABLE asekuracyjny_transfer DROP FOREIGN KEY `FK_2E24076DB7757258`');
        $this->addSql('ALTER TABLE asekuracyjny_transfer DROP FOREIGN KEY `FK_2E24076DE92F8F78`');
        $this->addSql('DROP TABLE asekuracyjny_equipment');
        $this->addSql('DROP TABLE asekuracyjny_equipment_set');
        $this->addSql('DROP TABLE asekuracyjny_equipment_set_items');
        $this->addSql('DROP TABLE asekuracyjny_review');
        $this->addSql('DROP TABLE asekuracyjny_transfer');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE asekuracyjny_equipment (id INT AUTO_INCREMENT NOT NULL, inventory_number VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, equipment_type VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, manufacturer VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, model VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, serial_number VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, manufacturing_date DATE DEFAULT NULL, purchase_date DATE DEFAULT NULL, purchase_price NUMERIC(10, 2) DEFAULT NULL, supplier VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, invoice_number VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, warranty_expiry DATE DEFAULT NULL, next_review_date DATE DEFAULT NULL, review_interval_months INT DEFAULT NULL, status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, assigned_date DATE DEFAULT NULL, location VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, custom_fields JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, assigned_to_id INT DEFAULT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_7B311A72896DBBDE (updated_by_id), INDEX IDX_7B311A72B03A8386 (created_by_id), INDEX IDX_7B311A72F4BD7827 (assigned_to_id), UNIQUE INDEX UNIQ_7B311A72964C83FF (inventory_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE asekuracyjny_equipment_set (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, set_type VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, assigned_date DATE DEFAULT NULL, next_review_date DATE DEFAULT NULL, review_interval_months INT DEFAULT NULL, location VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, custom_fields JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, assigned_to_id INT DEFAULT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_7A72DD91896DBBDE (updated_by_id), INDEX IDX_7A72DD91B03A8386 (created_by_id), INDEX IDX_7A72DD91F4BD7827 (assigned_to_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE asekuracyjny_equipment_set_items (asekuracyjny_equipment_set_id INT NOT NULL, asekuracyjny_equipment_id INT NOT NULL, INDEX IDX_5429FE4E100DBCCF (asekuracyjny_equipment_set_id), INDEX IDX_5429FE4ED0C78394 (asekuracyjny_equipment_id), PRIMARY KEY (asekuracyjny_equipment_set_id, asekuracyjny_equipment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE asekuracyjny_review (id INT AUTO_INCREMENT NOT NULL, review_number VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, review_type VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, planned_date DATE NOT NULL, sent_date DATE DEFAULT NULL, completed_date DATE DEFAULT NULL, next_review_date DATE DEFAULT NULL, review_company VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, certificate_number VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, result VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, findings LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, recommendations LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, cost NUMERIC(8, 2) DEFAULT NULL, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, selected_equipment_ids JSON DEFAULT NULL, attachments JSON DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, equipment_id INT DEFAULT NULL, equipment_set_id INT DEFAULT NULL, prepared_by_id INT NOT NULL, sent_by_id INT DEFAULT NULL, completed_by_id INT DEFAULT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_6A22B80CA45BB98C (sent_by_id), INDEX IDX_6A22B80C85ECDE76 (completed_by_id), INDEX IDX_6A22B80CB03A8386 (created_by_id), INDEX IDX_6A22B80C896DBBDE (updated_by_id), INDEX IDX_6A22B80C517FE9FE (equipment_id), INDEX IDX_6A22B80C393065A9 (prepared_by_id), INDEX IDX_6A22B80CB7757258 (equipment_set_id), UNIQUE INDEX UNIQ_6A22B80C1CE65894 (review_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE asekuracyjny_transfer (id INT AUTO_INCREMENT NOT NULL, transfer_number VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, transfer_date DATE NOT NULL, return_date DATE DEFAULT NULL, purpose LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, notes LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, conditions LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, location VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, selected_equipment_ids JSON DEFAULT NULL, protocol_scan_filename VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, protocol_uploaded_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, equipment_id INT DEFAULT NULL, equipment_set_id INT DEFAULT NULL, recipient_id INT NOT NULL, handed_by_id INT NOT NULL, returned_by_id INT DEFAULT NULL, created_by_id INT NOT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_2E24076DB7757258 (equipment_set_id), INDEX IDX_2E24076D896DBBDE (updated_by_id), INDEX IDX_2E24076DB03A8386 (created_by_id), INDEX IDX_2E24076D71AD87D9 (returned_by_id), INDEX IDX_2E24076D7C9FDA18 (handed_by_id), INDEX IDX_2E24076DE92F8F78 (recipient_id), INDEX IDX_2E24076D517FE9FE (equipment_id), UNIQUE INDEX UNIQ_2E24076DF3834267 (transfer_number), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE asekuracyjny_equipment ADD CONSTRAINT `FK_7B311A72896DBBDE` FOREIGN KEY (updated_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_equipment ADD CONSTRAINT `FK_7B311A72B03A8386` FOREIGN KEY (created_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_equipment ADD CONSTRAINT `FK_7B311A72F4BD7827` FOREIGN KEY (assigned_to_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_equipment_set ADD CONSTRAINT `FK_7A72DD91896DBBDE` FOREIGN KEY (updated_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_equipment_set ADD CONSTRAINT `FK_7A72DD91B03A8386` FOREIGN KEY (created_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_equipment_set ADD CONSTRAINT `FK_7A72DD91F4BD7827` FOREIGN KEY (assigned_to_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_equipment_set_items ADD CONSTRAINT `FK_5429FE4E100DBCCF` FOREIGN KEY (asekuracyjny_equipment_set_id) REFERENCES asekuracyjny_equipment_set (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE asekuracyjny_equipment_set_items ADD CONSTRAINT `FK_5429FE4ED0C78394` FOREIGN KEY (asekuracyjny_equipment_id) REFERENCES asekuracyjny_equipment (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE asekuracyjny_review ADD CONSTRAINT `FK_6A22B80C393065A9` FOREIGN KEY (prepared_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_review ADD CONSTRAINT `FK_6A22B80C517FE9FE` FOREIGN KEY (equipment_id) REFERENCES asekuracyjny_equipment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_review ADD CONSTRAINT `FK_6A22B80C85ECDE76` FOREIGN KEY (completed_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_review ADD CONSTRAINT `FK_6A22B80C896DBBDE` FOREIGN KEY (updated_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_review ADD CONSTRAINT `FK_6A22B80CA45BB98C` FOREIGN KEY (sent_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_review ADD CONSTRAINT `FK_6A22B80CB03A8386` FOREIGN KEY (created_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_review ADD CONSTRAINT `FK_6A22B80CB7757258` FOREIGN KEY (equipment_set_id) REFERENCES asekuracyjny_equipment_set (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_transfer ADD CONSTRAINT `FK_2E24076D517FE9FE` FOREIGN KEY (equipment_id) REFERENCES asekuracyjny_equipment (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_transfer ADD CONSTRAINT `FK_2E24076D71AD87D9` FOREIGN KEY (returned_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_transfer ADD CONSTRAINT `FK_2E24076D7C9FDA18` FOREIGN KEY (handed_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_transfer ADD CONSTRAINT `FK_2E24076D896DBBDE` FOREIGN KEY (updated_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_transfer ADD CONSTRAINT `FK_2E24076DB03A8386` FOREIGN KEY (created_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_transfer ADD CONSTRAINT `FK_2E24076DB7757258` FOREIGN KEY (equipment_set_id) REFERENCES asekuracyjny_equipment_set (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE asekuracyjny_transfer ADD CONSTRAINT `FK_2E24076DE92F8F78` FOREIGN KEY (recipient_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
