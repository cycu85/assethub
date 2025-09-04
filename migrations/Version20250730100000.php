<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Dictionary system migration
 */
final class Version20250730100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add dictionaries table for universal dictionary system';
    }

    public function up(Schema $schema): void
    {
        // Create dictionaries table
        $this->addSql('CREATE TABLE dictionaries (
            id INT AUTO_INCREMENT NOT NULL,
            parent_id INT DEFAULT NULL,
            type VARCHAR(100) NOT NULL,
            name VARCHAR(255) NOT NULL,
            value VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            color VARCHAR(50) DEFAULT NULL,
            icon VARCHAR(100) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            is_system TINYINT(1) NOT NULL DEFAULT 0,
            metadata JSON DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_4E1D094A727ACA70 (parent_id),
            INDEX IDX_4E1D094A8CDE5729 (type),
            INDEX IDX_4E1D094A8CDE5729_9F75D7B0 (type, is_active),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add foreign key constraint
        $this->addSql('ALTER TABLE dictionaries ADD CONSTRAINT FK_4E1D094A727ACA70 FOREIGN KEY (parent_id) REFERENCES dictionaries (id) ON DELETE CASCADE');

        // Insert sample dictionary data
        $this->insertSampleData();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE dictionaries');
    }

    private function insertSampleData(): void
    {
        $now = date('Y-m-d H:i:s');

        // Equipment Categories (pozostały dla słowników systemowych)
        $this->addSql("INSERT INTO dictionaries (type, name, value, description, color, icon, is_active, sort_order, is_system, created_at, updated_at) VALUES
            ('equipment_categories', 'Podstawowe', 'basic_equipment', 'Podstawowe kategorie sprzętu', '#3498db', 'ri-tools-line', 1, 1, 1, '$now', '$now')");

        // Equipment Statuses (pozostały dla słowników systemowych)
        $this->addSql("INSERT INTO dictionaries (type, name, value, description, color, icon, is_active, sort_order, is_system, created_at, updated_at) VALUES
            ('equipment_statuses', 'Dostępny', 'available', 'Status podstawowy', '#27ae60', 'ri-check-line', 1, 1, 1, '$now', '$now'),
            ('equipment_statuses', 'Niedostępny', 'unavailable', 'Status niedostępny', '#e74c3c', 'ri-close-line', 1, 2, 1, '$now', '$now')");

        // Locations
        $this->addSql("INSERT INTO dictionaries (type, name, value, description, color, icon, is_active, sort_order, is_system, created_at, updated_at) VALUES
            ('locations', 'Magazyn główny', 'main_warehouse', 'Główny magazyn sprzętu', '#2c3e50', 'ri-building-line', 1, 1, 1, '$now', '$now'),
            ('locations', 'Warsztat', 'workshop', 'Warsztat techniczny', '#34495e', 'ri-hammer-line', 1, 2, 1, '$now', '$now'),
            ('locations', 'Biuro', 'office', 'Pomieszczenia biurowe', '#3498db', 'ri-briefcase-line', 1, 3, 1, '$now', '$now'),
            ('locations', 'Plac budowy A', 'construction_site_a', 'Główny plac budowy', '#e67e22', 'ri-building-2-line', 1, 4, 1, '$now', '$now'),
            ('locations', 'Plac budowy B', 'construction_site_b', 'Drugi plac budowy', '#d35400', 'ri-building-3-line', 1, 5, 1, '$now', '$now')");

        // Priorities
        $this->addSql("INSERT INTO dictionaries (type, name, value, description, color, icon, is_active, sort_order, is_system, created_at, updated_at) VALUES
            ('priorities', 'Niski', 'low', 'Niski priorytet', '#95a5a6', 'ri-arrow-down-line', 1, 1, 1, '$now', '$now'),
            ('priorities', 'Normalny', 'normal', 'Standardowy priorytet', '#3498db', 'ri-subtract-line', 1, 2, 1, '$now', '$now'),
            ('priorities', 'Wysoki', 'high', 'Wysoki priorytet', '#f39c12', 'ri-arrow-up-line', 1, 3, 1, '$now', '$now'),
            ('priorities', 'Krytyczny', 'critical', 'Priorytet krytyczny', '#e74c3c', 'ri-alert-line', 1, 4, 1, '$now', '$now')");

        // Departments
        $this->addSql("INSERT INTO dictionaries (type, name, value, description, color, icon, is_active, sort_order, is_system, created_at, updated_at) VALUES
            ('departments', 'Administracja', 'administration', 'Dział administracyjny', '#3498db', 'ri-briefcase-line', 1, 1, 1, '$now', '$now'),
            ('departments', 'Produkcja', 'production', 'Dział produkcyjny', '#e67e22', 'ri-settings-line', 1, 2, 1, '$now', '$now'),
            ('departments', 'Logistyka', 'logistics', 'Dział logistyki i magazynu', '#1abc9c', 'ri-truck-line', 1, 3, 1, '$now', '$now'),
            ('departments', 'IT', 'it', 'Dział informatyczny', '#9b59b6', 'ri-computer-line', 1, 4, 1, '$now', '$now'),
            ('departments', 'HR', 'hr', 'Dział kadr', '#e74c3c', 'ri-team-line', 1, 5, 1, '$now', '$now')");

        // Equipment subcategories removed - old equipment module disabled
    }
}