<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250902070757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add AparaturaPomiarowa module and roles to system';
    }

    public function up(Schema $schema): void
    {
        // Create module and roles
        $this->addSql("INSERT INTO modules (name, display_name, description, is_enabled, created_at, updated_at) VALUES ('aparatura_pomiarowa', 'Aparatura Pomiarowa', 'Moduł zarządzania aparaturą pomiarową - mierniki i akcesoria', 1, NOW(), NOW())");
        
        $this->addSql("INSERT INTO roles (name, description, module_id, permissions, is_system_role, created_at, updated_at) 
                       SELECT 'APARATURA_POMIAROWA_ADMIN', 'Pełne uprawnienia do aparatury pomiarowej', 
                       id, '[\"VIEW\", \"CREATE\", \"EDIT\", \"DELETE\", \"ASSIGN\", \"TRANSFER\", \"REVIEW\"]', 0, NOW(), NOW() 
                       FROM modules WHERE name = 'aparatura_pomiarowa'");
        
        $this->addSql("INSERT INTO roles (name, description, module_id, permissions, is_system_role, created_at, updated_at) 
                       SELECT 'APARATURA_POMIAROWA_USER', 'Podstawowe uprawnienia do aparatury pomiarowej', 
                       id, '[\"VIEW\", \"CREATE\", \"EDIT\", \"ASSIGN\", \"REVIEW\"]', 0, NOW(), NOW() 
                       FROM modules WHERE name = 'aparatura_pomiarowa'");
        
        $this->addSql("INSERT INTO roles (name, description, module_id, permissions, is_system_role, created_at, updated_at) 
                       SELECT 'APARATURA_POMIAROWA_VIEWER', 'Uprawnienia tylko do odczytu aparatury pomiarowej', 
                       id, '[\"VIEW\"]', 0, NOW(), NOW() 
                       FROM modules WHERE name = 'aparatura_pomiarowa'");
        
        $this->addSql("INSERT INTO roles (name, description, module_id, permissions, is_system_role, created_at, updated_at) 
                       SELECT 'APARATURA_POMIAROWA_OWN', 'Uprawnienia do zarządzania własną aparaturą pomiarową', 
                       id, '[\"VIEW_OWN\"]', 0, NOW(), NOW() 
                       FROM modules WHERE name = 'aparatura_pomiarowa'");
    }

    public function down(Schema $schema): void
    {
        // Remove module and roles
        $this->addSql('DELETE FROM user_roles WHERE role_id IN (SELECT id FROM roles WHERE module_id IN (SELECT id FROM modules WHERE name = "aparatura_pomiarowa"))');
        $this->addSql('DELETE FROM roles WHERE module_id IN (SELECT id FROM modules WHERE name = "aparatura_pomiarowa")');
        $this->addSql('DELETE FROM modules WHERE name = "aparatura_pomiarowa"');
    }
}
