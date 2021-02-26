<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201211153356_add_sirtac_tax extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO `province_withholding_tax_setting` (
                `province_id`, `withholding_tax_type`, `withholding_tax_system`, `type`, `code`,
                `agent_subsidiary`, `last_certificate`, `period`, `min_amount`, `resolution`, `number`
            ) VALUES (
                0, 'SIRTAC', 'SIRTAC', NULL, NULL,
                NULL, 0, 'SEMI_MONTHLY', 0.00, 'RG XXXXXX', 'XX-XXXXXXXX-X'
            );
        ");

        /*
         * Este sistema tiene alcance a usuarios ocasionales, profesionales y empresas.
         *
         * Se deberá contemplar SIRTAC como un proceso nacional
         *
         * Aplica regla de habitualidad en el transcurso del mes calendario
         */
        $this->addSql("
            INSERT INTO `withholding_tax_rule` (
                `type`, `tax_category_id`, `province_id`, `unpublish_rate`, `minimum_amount`, `calculation_basis`,
                `withhold_occasional`, `has_tax_registry`, `period`, `download_date_db`, `modified_at`, `created_at`, `rate`
            ) VALUES (
                'SIRTAC', NULL, NULL, 0, 0, 'GROSS',
                1, 1, 'This Month', NULL, NULL, NOW(), 0
            );
        ");

        /*
         * Mínimo no sujeto a retención: NO tiene
         *
         * Reglas Simples.
         * Exento: 0%
         * No inscripto: Habitualidad
         */
        $this->addSql("
            INSERT INTO `withholding_tax_simple_rule` (
                `type`, `province_id`, `tax_category_id`, `rate`, `minimum_amount`, `created_at`,
                `classification_id`, `tax_condition_id`, `income_tax`, `payment_method_type`, `provinces_group_id`
            ) VALUES (
                'SIRTAC', NULL, 5, 0.00, 0.00, NOW(), NULL, NULL, NULL, NULL, 1
            );
        ");

        // Hard-rule va por codigo
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            DELETE FROM `withholding_tax_simple_rule` WHERE type = 'SIRTAC';
        ");

        $this->addSql("
            DELETE FROM `withholding_tax_rule` WHERE type = 'SIRTAC';
        ");
    }
}
