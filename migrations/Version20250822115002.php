<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250822115002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE asekuracyjny_equipment ADD projekt VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_dictionary_type ON dictionaries');
        $this->addSql('DROP INDEX idx_dictionary_parent ON dictionaries');
        $this->addSql('DROP INDEX idx_dictionary_active ON dictionaries');
        $this->addSql('DROP INDEX idx_equipment_warranty ON equipment');
        $this->addSql('DROP INDEX idx_equipment_serial ON equipment');
        $this->addSql('DROP INDEX idx_equipment_inventory ON equipment');
        $this->addSql('DROP INDEX idx_equipment_assigned ON equipment');
        $this->addSql('DROP INDEX idx_equipment_category ON equipment');
        $this->addSql('DROP INDEX idx_equipment_status ON equipment');
        $this->addSql('DROP INDEX idx_equipment_log_user ON equipment_log');
        $this->addSql('DROP INDEX idx_equipment_log_equipment ON equipment_log');
        $this->addSql('DROP INDEX idx_equipment_log_date ON equipment_log');
        $this->addSql('DROP INDEX idx_setting_key ON settings');
        $this->addSql('DROP INDEX idx_setting_category ON settings');
        $this->addSql('DROP INDEX idx_user_email ON users');
        $this->addSql('DROP INDEX idx_user_active ON users');
        $this->addSql('DROP INDEX idx_user_ldap_dn ON users');
        $this->addSql('DROP INDEX idx_user_name_search ON users');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE asekuracyjny_equipment DROP projekt');
        $this->addSql('CREATE INDEX idx_dictionary_type ON dictionaries (type)');
        $this->addSql('CREATE INDEX idx_dictionary_parent ON dictionaries (parent_id)');
        $this->addSql('CREATE INDEX idx_dictionary_active ON dictionaries (is_active)');
        $this->addSql('CREATE INDEX idx_equipment_warranty ON equipment (warranty_expiry)');
        $this->addSql('CREATE INDEX idx_equipment_serial ON equipment (serial_number)');
        $this->addSql('CREATE INDEX idx_equipment_inventory ON equipment (inventory_number)');
        $this->addSql('CREATE INDEX idx_equipment_assigned ON equipment (assigned_to_id)');
        $this->addSql('CREATE INDEX idx_equipment_category ON equipment (category_id)');
        $this->addSql('CREATE INDEX idx_equipment_status ON equipment (status)');
        $this->addSql('CREATE INDEX idx_equipment_log_user ON equipment_log (created_by_id)');
        $this->addSql('CREATE INDEX idx_equipment_log_equipment ON equipment_log (equipment_id)');
        $this->addSql('CREATE INDEX idx_equipment_log_date ON equipment_log (created_at)');
        $this->addSql('CREATE INDEX idx_setting_key ON settings (setting_key)');
        $this->addSql('CREATE INDEX idx_setting_category ON settings (category)');
        $this->addSql('CREATE INDEX idx_user_email ON `users` (email)');
        $this->addSql('CREATE INDEX idx_user_active ON `users` (is_active)');
        $this->addSql('CREATE INDEX idx_user_ldap_dn ON `users` (ldap_dn)');
        $this->addSql('CREATE INDEX idx_user_name_search ON `users` (last_name, first_name)');
    }
}
