<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250824182142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ASSEK_USER role for safety equipment users with limited permissions';
    }

    public function up(Schema $schema): void
    {
        // Add ASSEK_USER role to asekuracja module
        $this->addSql("
            INSERT INTO roles (name, description, permissions, module_id, is_system_role, created_at, updated_at) 
            SELECT 
                'ASSEK_USER',
                'Użytkownik Asekuracji - widzi tylko swoje przypisane zestawy',
                '[\"VIEW_OWN\"]',
                m.id,
                0,
                NOW(),
                NOW()
            FROM modules m 
            WHERE m.name = 'asekuracja'
            AND NOT EXISTS (
                SELECT 1 FROM roles r WHERE r.name = 'ASSEK_USER' AND r.module_id = m.id
            )
        ");
    }

    public function down(Schema $schema): void
    {
        // Remove ASSEK_USER role
        $this->addSql("
            DELETE FROM roles 
            WHERE name = 'ASSEK_USER' 
            AND module_id = (SELECT id FROM modules WHERE name = 'asekuracja')
        ");
    }
}
