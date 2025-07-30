<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Clean MySQL Migration for AssetHub System
 */
final class Version20250730080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clean database schema for AssetHub - MySQL version';
    }

    public function up(Schema $schema): void
    {
        // Create modules table
        $this->addSql('CREATE TABLE modules (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(50) NOT NULL,
            display_name VARCHAR(100) NOT NULL,
            description TEXT DEFAULT NULL,
            is_enabled TINYINT(1) NOT NULL,
            required_permissions JSON DEFAULT NULL,
            config JSON DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_modules_name (name)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create users table
        $this->addSql('CREATE TABLE users (
            id INT AUTO_INCREMENT NOT NULL,
            username VARCHAR(180) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            employee_number VARCHAR(50) DEFAULT NULL,
            position VARCHAR(100) DEFAULT NULL,
            department VARCHAR(100) DEFAULT NULL,
            phone_number VARCHAR(20) DEFAULT NULL,
            ldap_dn VARCHAR(500) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_users_username (username),
            UNIQUE INDEX UNIQ_users_email (email)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create roles table
        $this->addSql('CREATE TABLE roles (
            id INT AUTO_INCREMENT NOT NULL,
            module_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT DEFAULT NULL,
            permissions JSON NOT NULL,
            is_system_role TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_roles_module (module_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create user_roles table
        $this->addSql('CREATE TABLE user_roles (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            role_id INT NOT NULL,
            assigned_by_id INT DEFAULT NULL,
            assigned_at DATETIME NOT NULL,
            is_active TINYINT(1) NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_user_roles_user (user_id),
            INDEX IDX_user_roles_role (role_id),
            INDEX IDX_user_roles_assigned_by (assigned_by_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create equipment_categories table
        $this->addSql('CREATE TABLE equipment_categories (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            color VARCHAR(50) DEFAULT NULL,
            icon VARCHAR(100) DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            custom_fields_config JSON DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_equipment_categories_name (name)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create equipment table
        $this->addSql('CREATE TABLE equipment (
            id INT AUTO_INCREMENT NOT NULL,
            category_id INT NOT NULL,
            assigned_to_id INT DEFAULT NULL,
            created_by_id INT NOT NULL,
            updated_by_id INT DEFAULT NULL,
            inventory_number VARCHAR(100) NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            manufacturer VARCHAR(255) DEFAULT NULL,
            model VARCHAR(255) DEFAULT NULL,
            serial_number VARCHAR(100) DEFAULT NULL,
            purchase_date DATE DEFAULT NULL,
            purchase_price DECIMAL(10,2) DEFAULT NULL,
            warranty_expiry DATE DEFAULT NULL,
            next_inspection_date DATE DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT "available",
            location VARCHAR(255) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            custom_fields JSON DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_equipment_inventory_number (inventory_number),
            INDEX IDX_equipment_category (category_id),
            INDEX IDX_equipment_assigned_to (assigned_to_id),
            INDEX IDX_equipment_created_by (created_by_id),
            INDEX IDX_equipment_updated_by (updated_by_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create equipment_log table
        $this->addSql('CREATE TABLE equipment_log (
            id INT AUTO_INCREMENT NOT NULL,
            equipment_id INT NOT NULL,
            created_by_id INT NOT NULL,
            previous_assignee_id INT DEFAULT NULL,
            new_assignee_id INT DEFAULT NULL,
            action VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            previous_status VARCHAR(50) DEFAULT NULL,
            new_status VARCHAR(50) DEFAULT NULL,
            additional_data JSON DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_equipment_log_equipment (equipment_id),
            INDEX IDX_equipment_log_created_by (created_by_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create equipment_attachment table
        $this->addSql('CREATE TABLE equipment_attachment (
            id INT AUTO_INCREMENT NOT NULL,
            equipment_id INT NOT NULL,
            uploaded_by_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            description TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_equipment_attachment_equipment (equipment_id),
            INDEX IDX_equipment_attachment_uploaded_by (uploaded_by_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE equipment_attachment');
        $this->addSql('DROP TABLE equipment_log');
        $this->addSql('DROP TABLE equipment');
        $this->addSql('DROP TABLE equipment_categories');
        $this->addSql('DROP TABLE user_roles');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE modules');
    }
}