<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250902112214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aparatura_pomiarowa_equipment_set_items (aparatura_pomiarowa_equipment_set_id INT NOT NULL, aparatura_pomiarowa_equipment_id INT NOT NULL, INDEX IDX_77D6DA5B35E53A4E (aparatura_pomiarowa_equipment_set_id), INDEX IDX_77D6DA5BB4DCD73A (aparatura_pomiarowa_equipment_id), PRIMARY KEY (aparatura_pomiarowa_equipment_set_id, aparatura_pomiarowa_equipment_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set_items ADD CONSTRAINT FK_77D6DA5B35E53A4E FOREIGN KEY (aparatura_pomiarowa_equipment_set_id) REFERENCES aparatura_pomiarowa_equipment_set (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set_items ADD CONSTRAINT FK_77D6DA5BB4DCD73A FOREIGN KEY (aparatura_pomiarowa_equipment_id) REFERENCES aparatura_pomiarowa_equipment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set_equipment DROP FOREIGN KEY `FK_AP_ESE_100DBCCF`');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set_equipment DROP FOREIGN KEY `FK_AP_ESE_D0C78394`');
        $this->addSql('DROP TABLE aparatura_pomiarowa_equipment_set_equipment');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment ADD projekt VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment RENAME INDEX uniq_ap_eq_964c83ff TO UNIQ_E80F9138964C83FF');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment RENAME INDEX idx_ap_eq_f4bd7827 TO IDX_E80F9138F4BD7827');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment RENAME INDEX idx_ap_eq_b03a8386 TO IDX_E80F9138B03A8386');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment RENAME INDEX idx_ap_eq_896dbbde TO IDX_E80F9138896DBBDE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set RENAME INDEX idx_ap_es_f4bd7827 TO IDX_4B78482AF4BD7827');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set RENAME INDEX idx_ap_es_b03a8386 TO IDX_4B78482AB03A8386');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set RENAME INDEX idx_ap_es_896dbbde TO IDX_4B78482A896DBBDE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX uniq_ap_r_1ce65894 TO UNIQ_3AA613271CE65894');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_ap_r_517fe9fe TO IDX_3AA61327517FE9FE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_ap_r_b7757258 TO IDX_3AA61327B7757258');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_ap_r_393065a9 TO IDX_3AA61327393065A9');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_ap_r_a45bb98c TO IDX_3AA61327A45BB98C');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_ap_r_85ecde76 TO IDX_3AA6132785ECDE76');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_ap_r_b03a8386 TO IDX_3AA61327B03A8386');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_ap_r_896dbbde TO IDX_3AA61327896DBBDE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review_equipment RENAME INDEX idx_ap_re_3e2e969b TO IDX_C65906943E2E969B');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review_equipment RENAME INDEX idx_ap_re_517fe9fe TO IDX_C6590694517FE9FE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review_equipment RENAME INDEX unique_aparatura_review_equipment TO unique_review_equipment');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer ADD return_protocol_filename VARCHAR(255) DEFAULT NULL, ADD return_protocol_uploaded_at DATETIME DEFAULT NULL, ADD return_notes LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX uniq_ap_t_f3834267 TO UNIQ_1950D0E0F3834267');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_ap_t_517fe9fe TO IDX_1950D0E0517FE9FE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_ap_t_b7757258 TO IDX_1950D0E0B7757258');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_ap_t_e92f8f78 TO IDX_1950D0E0E92F8F78');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_ap_t_7c9fda18 TO IDX_1950D0E07C9FDA18');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_ap_t_71ad87d9 TO IDX_1950D0E071AD87D9');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_ap_t_b03a8386 TO IDX_1950D0E0B03A8386');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_ap_t_896dbbde TO IDX_1950D0E0896DBBDE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aparatura_pomiarowa_equipment_set_equipment (aparatura_pomiarowa_equipment_set_id INT NOT NULL, aparatura_pomiarowa_equipment_id INT NOT NULL, INDEX IDX_AP_ESE_100DBCCF (aparatura_pomiarowa_equipment_set_id), INDEX IDX_AP_ESE_D0C78394 (aparatura_pomiarowa_equipment_id), PRIMARY KEY (aparatura_pomiarowa_equipment_set_id, aparatura_pomiarowa_equipment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set_equipment ADD CONSTRAINT `FK_AP_ESE_100DBCCF` FOREIGN KEY (aparatura_pomiarowa_equipment_set_id) REFERENCES aparatura_pomiarowa_equipment_set (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set_equipment ADD CONSTRAINT `FK_AP_ESE_D0C78394` FOREIGN KEY (aparatura_pomiarowa_equipment_id) REFERENCES aparatura_pomiarowa_equipment (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set_items DROP FOREIGN KEY FK_77D6DA5B35E53A4E');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set_items DROP FOREIGN KEY FK_77D6DA5BB4DCD73A');
        $this->addSql('DROP TABLE aparatura_pomiarowa_equipment_set_items');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment DROP projekt');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment RENAME INDEX uniq_e80f9138964c83ff TO UNIQ_AP_EQ_964C83FF');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment RENAME INDEX idx_e80f9138f4bd7827 TO IDX_AP_EQ_F4BD7827');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment RENAME INDEX idx_e80f9138b03a8386 TO IDX_AP_EQ_B03A8386');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment RENAME INDEX idx_e80f9138896dbbde TO IDX_AP_EQ_896DBBDE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set RENAME INDEX idx_4b78482af4bd7827 TO IDX_AP_ES_F4BD7827');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set RENAME INDEX idx_4b78482ab03a8386 TO IDX_AP_ES_B03A8386');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_equipment_set RENAME INDEX idx_4b78482a896dbbde TO IDX_AP_ES_896DBBDE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX uniq_3aa613271ce65894 TO UNIQ_AP_R_1CE65894');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_3aa61327517fe9fe TO IDX_AP_R_517FE9FE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_3aa61327b7757258 TO IDX_AP_R_B7757258');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_3aa61327393065a9 TO IDX_AP_R_393065A9');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_3aa61327a45bb98c TO IDX_AP_R_A45BB98C');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_3aa6132785ecde76 TO IDX_AP_R_85ECDE76');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_3aa61327b03a8386 TO IDX_AP_R_B03A8386');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review RENAME INDEX idx_3aa61327896dbbde TO IDX_AP_R_896DBBDE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review_equipment RENAME INDEX unique_review_equipment TO unique_aparatura_review_equipment');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review_equipment RENAME INDEX idx_c65906943e2e969b TO IDX_AP_RE_3E2E969B');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_review_equipment RENAME INDEX idx_c6590694517fe9fe TO IDX_AP_RE_517FE9FE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer DROP return_protocol_filename, DROP return_protocol_uploaded_at, DROP return_notes');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX uniq_1950d0e0f3834267 TO UNIQ_AP_T_F3834267');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_1950d0e0517fe9fe TO IDX_AP_T_517FE9FE');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_1950d0e0b7757258 TO IDX_AP_T_B7757258');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_1950d0e0e92f8f78 TO IDX_AP_T_E92F8F78');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_1950d0e07c9fda18 TO IDX_AP_T_7C9FDA18');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_1950d0e071ad87d9 TO IDX_AP_T_71AD87D9');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_1950d0e0b03a8386 TO IDX_AP_T_B03A8386');
        $this->addSql('ALTER TABLE aparatura_pomiarowa_transfer RENAME INDEX idx_1950d0e0896dbbde TO IDX_AP_T_896DBBDE');
    }
}
