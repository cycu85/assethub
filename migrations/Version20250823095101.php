<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250823095101 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Re-add projekt column to asekuracyjny_equipment table';
    }

    public function up(Schema $schema): void
    {
        // Re-add the projekt column that was accidentally removed in previous migration
        $this->addSql('ALTER TABLE asekuracyjny_equipment ADD projekt VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove the projekt column
        $this->addSql('ALTER TABLE asekuracyjny_equipment DROP COLUMN projekt');
    }
}
