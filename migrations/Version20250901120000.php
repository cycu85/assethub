<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Cleanup AparaturaPomiarowa tables - remove module completely
 */
final class Version20250901120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clean up AparaturaPomiarowa module - remove all tables and references';
    }

    public function up(Schema $schema): void
    {
        // Remove AparaturaPomiarowa tables if they exist
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        $this->addSql('DROP TABLE IF EXISTS aparatura_pomiarowa_review_equipment');
        $this->addSql('DROP TABLE IF EXISTS aparatura_pomiarowa_review');
        $this->addSql('DROP TABLE IF EXISTS aparatura_pomiarowa_transfer');
        $this->addSql('DROP TABLE IF EXISTS aparatura_pomiarowa_equipment_set_equipment');
        $this->addSql('DROP TABLE IF EXISTS aparatura_pomiarowa_equipment_set');
        $this->addSql('DROP TABLE IF EXISTS aparatura_pomiarowa_equipment');
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
        
        // Remove AparaturaPomiarowa module and roles from system
        $this->addSql('DELETE FROM user_roles WHERE role_id IN (SELECT id FROM roles WHERE module_id IN (SELECT id FROM modules WHERE name = "aparatura_pomiarowa"))');
        $this->addSql('DELETE FROM roles WHERE module_id IN (SELECT id FROM modules WHERE name = "aparatura_pomiarowa")');
        $this->addSql('DELETE FROM modules WHERE name = "aparatura_pomiarowa"');
    }

    public function down(Schema $schema): void
    {
        // This migration cannot be reversed as it would require recreating the entire AparaturaPomiarowa module
        throw new \RuntimeException('This migration cannot be reversed. The AparaturaPomiarowa module has been permanently removed.');
    }
}