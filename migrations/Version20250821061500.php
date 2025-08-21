<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to populate asekuracyjny_review_equipment table with existing review data
 */
final class Version20250821061500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migracja danych istniejących przeglądów do nowej tabeli asekuracyjny_review_equipment';
    }

    public function up(Schema $schema): void
    {
        // Migrate individual equipment reviews
        $this->addSql("
            INSERT INTO asekuracyjny_review_equipment (
                review_id, 
                equipment_id, 
                equipment_status_at_review,
                equipment_name_at_review,
                equipment_inventory_number_at_review,
                equipment_type_at_review,
                equipment_manufacturer_at_review,
                equipment_model_at_review,
                equipment_serial_number_at_review,
                equipment_next_review_date_at_review,
                was_in_set_at_review,
                set_name_at_review,
                created_at
            )
            SELECT 
                r.id as review_id,
                e.id as equipment_id,
                e.status as equipment_status_at_review,
                e.name as equipment_name_at_review,
                e.inventory_number as equipment_inventory_number_at_review,
                e.equipment_type as equipment_type_at_review,
                e.manufacturer as equipment_manufacturer_at_review,
                e.model as equipment_model_at_review,
                e.serial_number as equipment_serial_number_at_review,
                e.next_review_date as equipment_next_review_date_at_review,
                0 as was_in_set_at_review,
                NULL as set_name_at_review,
                r.created_at as created_at
            FROM asekuracyjny_review r
            INNER JOIN asekuracyjny_equipment e ON r.equipment_id = e.id
            WHERE r.equipment_id IS NOT NULL
        ");

        // Migrate equipment set reviews - for all equipment in the set
        $this->addSql("
            INSERT INTO asekuracyjny_review_equipment (
                review_id, 
                equipment_id, 
                equipment_status_at_review,
                equipment_name_at_review,
                equipment_inventory_number_at_review,
                equipment_type_at_review,
                equipment_manufacturer_at_review,
                equipment_model_at_review,
                equipment_serial_number_at_review,
                equipment_next_review_date_at_review,
                was_in_set_at_review,
                set_name_at_review,
                created_at
            )
            SELECT 
                r.id as review_id,
                e.id as equipment_id,
                e.status as equipment_status_at_review,
                e.name as equipment_name_at_review,
                e.inventory_number as equipment_inventory_number_at_review,
                e.equipment_type as equipment_type_at_review,
                e.manufacturer as equipment_manufacturer_at_review,
                e.model as equipment_model_at_review,
                e.serial_number as equipment_serial_number_at_review,
                e.next_review_date as equipment_next_review_date_at_review,
                1 as was_in_set_at_review,
                es.name as set_name_at_review,
                r.created_at as created_at
            FROM asekuracyjny_review r
            INNER JOIN asekuracyjny_equipment_set es ON r.equipment_set_id = es.id
            INNER JOIN asekuracyjny_equipment_set_items esi ON es.id = esi.asekuracyjny_equipment_set_id
            INNER JOIN asekuracyjny_equipment e ON esi.asekuracyjny_equipment_id = e.id
            WHERE r.equipment_set_id IS NOT NULL
        ");

        // Handle equipment set reviews with selected equipment (from JSON field)
        $this->addSql("
            INSERT INTO asekuracyjny_review_equipment (
                review_id, 
                equipment_id, 
                equipment_status_at_review,
                equipment_name_at_review,
                equipment_inventory_number_at_review,
                equipment_type_at_review,
                equipment_manufacturer_at_review,
                equipment_model_at_review,
                equipment_serial_number_at_review,
                equipment_next_review_date_at_review,
                was_in_set_at_review,
                set_name_at_review,
                created_at
            )
            SELECT 
                r.id as review_id,
                e.id as equipment_id,
                e.status as equipment_status_at_review,
                e.name as equipment_name_at_review,
                e.inventory_number as equipment_inventory_number_at_review,
                e.equipment_type as equipment_type_at_review,
                e.manufacturer as equipment_manufacturer_at_review,
                e.model as equipment_model_at_review,
                e.serial_number as equipment_serial_number_at_review,
                e.next_review_date as equipment_next_review_date_at_review,
                1 as was_in_set_at_review,
                es.name as set_name_at_review,
                r.created_at as created_at
            FROM asekuracyjny_review r
            INNER JOIN asekuracyjny_equipment_set es ON r.equipment_set_id = es.id
            INNER JOIN asekuracyjny_equipment e ON JSON_CONTAINS(r.selected_equipment_ids, CAST(e.id AS JSON))
            WHERE r.equipment_set_id IS NOT NULL 
            AND JSON_LENGTH(r.selected_equipment_ids) > 0
            AND NOT EXISTS (
                SELECT 1 FROM asekuracyjny_review_equipment re 
                WHERE re.review_id = r.id AND re.equipment_id = e.id
            )
        ");
    }

    public function down(Schema $schema): void
    {
        // Remove all migrated data
        $this->addSql('DELETE FROM asekuracyjny_review_equipment');
    }
}