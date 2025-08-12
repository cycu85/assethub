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
        // Sprawdź najpierw czy tabele istnieją, jeśli nie - pomiń tworzenie indeksów
        
        // Indeksy dla tabeli users - jeśli tabela istnieje
        $this->skipIf(!$schema->hasTable('users'), 'Tabela users nie istnieje');
        if ($schema->hasTable('users')) {
            $table = $schema->getTable('users');
            
            if (!$table->hasIndex('idx_user_email')) {
                $this->addSql('CREATE INDEX idx_user_email ON users (email)');
            }
            if (!$table->hasIndex('idx_user_active')) {
                $this->addSql('CREATE INDEX idx_user_active ON users (is_active)');
            }
            if (!$table->hasIndex('idx_user_ldap_dn')) {
                $this->addSql('CREATE INDEX idx_user_ldap_dn ON users (ldap_dn)');
            }
            if (!$table->hasIndex('idx_user_name_search')) {
                $this->addSql('CREATE INDEX idx_user_name_search ON users (last_name, first_name)');
            }
        }
        
        // Indeksy dla tabeli equipment - jeśli tabela istnieje
        if ($schema->hasTable('equipment')) {
            $table = $schema->getTable('equipment');
            
            if (!$table->hasIndex('idx_equipment_status')) {
                $this->addSql('CREATE INDEX idx_equipment_status ON equipment (status)');
            }
            if (!$table->hasIndex('idx_equipment_category')) {
                $this->addSql('CREATE INDEX idx_equipment_category ON equipment (category_id)');
            }
            if (!$table->hasIndex('idx_equipment_assigned')) {
                $this->addSql('CREATE INDEX idx_equipment_assigned ON equipment (assigned_to_id)');
            }
            if (!$table->hasIndex('idx_equipment_inventory')) {
                $this->addSql('CREATE INDEX idx_equipment_inventory ON equipment (inventory_number)');
            }
            if (!$table->hasIndex('idx_equipment_serial')) {
                $this->addSql('CREATE INDEX idx_equipment_serial ON equipment (serial_number)');
            }
            if (!$table->hasIndex('idx_equipment_warranty')) {
                $this->addSql('CREATE INDEX idx_equipment_warranty ON equipment (warranty_expiry)');
            }
        }
        
        // Indeksy dla tabeli equipment_log - jeśli tabela istnieje
        if ($schema->hasTable('equipment_log')) {
            $table = $schema->getTable('equipment_log');
            
            if (!$table->hasIndex('idx_equipment_log_date')) {
                $this->addSql('CREATE INDEX idx_equipment_log_date ON equipment_log (created_at)');
            }
            if (!$table->hasIndex('idx_equipment_log_equipment')) {
                $this->addSql('CREATE INDEX idx_equipment_log_equipment ON equipment_log (equipment_id)');
            }
            if (!$table->hasIndex('idx_equipment_log_user')) {
                $this->addSql('CREATE INDEX idx_equipment_log_user ON equipment_log (created_by_id)');
            }
        }
        
        // Indeksy dla tabeli settings - jeśli tabela istnieje
        if ($schema->hasTable('settings')) {
            $table = $schema->getTable('settings');
            
            if (!$table->hasIndex('idx_setting_key')) {
                $this->addSql('CREATE INDEX idx_setting_key ON settings (setting_key)');
            }
            if (!$table->hasIndex('idx_setting_category')) {
                $this->addSql('CREATE INDEX idx_setting_category ON settings (category)');
            }
        }
        
        // Indeksy dla tabeli dictionaries - jeśli tabela istnieje
        if ($schema->hasTable('dictionaries')) {
            $table = $schema->getTable('dictionaries');
            
            if (!$table->hasIndex('idx_dictionary_type')) {
                $this->addSql('CREATE INDEX idx_dictionary_type ON dictionaries (type)');
            }
            if (!$table->hasIndex('idx_dictionary_parent')) {
                $this->addSql('CREATE INDEX idx_dictionary_parent ON dictionaries (parent_id)');
            }
            if (!$table->hasIndex('idx_dictionary_active')) {
                $this->addSql('CREATE INDEX idx_dictionary_active ON dictionaries (is_active)');
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Usuwanie indeksów w odwrotnej kolejności
        $this->addSql('DROP INDEX IF EXISTS idx_dictionary_active ON dictionaries');
        $this->addSql('DROP INDEX IF EXISTS idx_dictionary_parent ON dictionaries');
        $this->addSql('DROP INDEX IF EXISTS idx_dictionary_type ON dictionaries');
        
        $this->addSql('DROP INDEX IF EXISTS idx_setting_category ON settings');
        $this->addSql('DROP INDEX IF EXISTS idx_setting_key ON settings');
        
        $this->addSql('DROP INDEX IF EXISTS idx_equipment_log_user ON equipment_log');
        $this->addSql('DROP INDEX IF EXISTS idx_equipment_log_equipment ON equipment_log');
        $this->addSql('DROP INDEX IF EXISTS idx_equipment_log_date ON equipment_log');
        
        $this->addSql('DROP INDEX IF EXISTS idx_equipment_warranty ON equipment');
        $this->addSql('DROP INDEX IF EXISTS idx_equipment_serial ON equipment');
        $this->addSql('DROP INDEX IF EXISTS idx_equipment_inventory ON equipment');
        $this->addSql('DROP INDEX IF EXISTS idx_equipment_assigned ON equipment');
        $this->addSql('DROP INDEX IF EXISTS idx_equipment_category ON equipment');
        $this->addSql('DROP INDEX IF EXISTS idx_equipment_status ON equipment');
        
        $this->addSql('DROP INDEX IF EXISTS idx_user_name_search ON users');
        $this->addSql('DROP INDEX IF EXISTS idx_user_ldap_dn ON users');
        $this->addSql('DROP INDEX IF EXISTS idx_user_active ON users');
        $this->addSql('DROP INDEX IF EXISTS idx_user_email ON users');
    }
}
