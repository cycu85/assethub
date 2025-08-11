<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250811105836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Optymalizacja indeksów dla lepszej wydajności zapytań';
    }

    public function up(Schema $schema): void
    {
        // Indeksy dla tabeli user - często wyszukiwane pola
        $this->addSql('CREATE INDEX idx_user_email ON user (email)');
        $this->addSql('CREATE INDEX idx_user_active ON user (is_active)');
        $this->addSql('CREATE INDEX idx_user_ldap_dn ON user (ldap_dn)');
        $this->addSql('CREATE INDEX idx_user_name_search ON user (last_name, first_name)');
        
        // Indeksy dla tabeli equipment - często używane w wyszukiwaniach
        $this->addSql('CREATE INDEX idx_equipment_status ON equipment (status)');
        $this->addSql('CREATE INDEX idx_equipment_category ON equipment (category_id)');
        $this->addSql('CREATE INDEX idx_equipment_assigned ON equipment (assigned_to_id)');
        $this->addSql('CREATE INDEX idx_equipment_inventory ON equipment (inventory_number)');
        $this->addSql('CREATE INDEX idx_equipment_serial ON equipment (serial_number)');
        $this->addSql('CREATE INDEX idx_equipment_warranty ON equipment (warranty_expiry)');
        
        // Indeksy dla tabeli equipment_log - logi są często sortowane po dacie
        $this->addSql('CREATE INDEX idx_equipment_log_date ON equipment_log (log_date)');
        $this->addSql('CREATE INDEX idx_equipment_log_equipment ON equipment_log (equipment_id)');
        $this->addSql('CREATE INDEX idx_equipment_log_user ON equipment_log (user_id)');
        
        // Indeksy dla tabeli setting - często używane do pobierania konfiguracji
        $this->addSql('CREATE INDEX idx_setting_key ON setting (setting_key)');
        $this->addSql('CREATE INDEX idx_setting_category ON setting (category)');
        
        // Indeksy dla tabeli dictionary - hierarchiczne wyszukiwanie
        $this->addSql('CREATE INDEX idx_dictionary_type ON dictionary (type)');
        $this->addSql('CREATE INDEX idx_dictionary_parent ON dictionary (parent_id)');
        $this->addSql('CREATE INDEX idx_dictionary_active ON dictionary (is_active)');
    }

    public function down(Schema $schema): void
    {
        // Usuwanie indeksów w odwrotnej kolejności
        $this->addSql('DROP INDEX idx_dictionary_active ON dictionary');
        $this->addSql('DROP INDEX idx_dictionary_parent ON dictionary');
        $this->addSql('DROP INDEX idx_dictionary_type ON dictionary');
        
        $this->addSql('DROP INDEX idx_setting_category ON setting');
        $this->addSql('DROP INDEX idx_setting_key ON setting');
        
        $this->addSql('DROP INDEX idx_equipment_log_user ON equipment_log');
        $this->addSql('DROP INDEX idx_equipment_log_equipment ON equipment_log');
        $this->addSql('DROP INDEX idx_equipment_log_date ON equipment_log');
        
        $this->addSql('DROP INDEX idx_equipment_warranty ON equipment');
        $this->addSql('DROP INDEX idx_equipment_serial ON equipment');
        $this->addSql('DROP INDEX idx_equipment_inventory ON equipment');
        $this->addSql('DROP INDEX idx_equipment_assigned ON equipment');
        $this->addSql('DROP INDEX idx_equipment_category ON equipment');
        $this->addSql('DROP INDEX idx_equipment_status ON equipment');
        
        $this->addSql('DROP INDEX idx_user_name_search ON user');
        $this->addSql('DROP INDEX idx_user_ldap_dn ON user');
        $this->addSql('DROP INDEX idx_user_active ON user');
        $this->addSql('DROP INDEX idx_user_email ON user');
    }
}
