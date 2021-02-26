<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201201190840_withholding_tax_add_chaco_rules extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $chacoProvinceId = 22;

        /*
         * Diseño del archivo de declaración: SIRCAR
         * El diseño es el SIRCAR (El modelo original, el que sale para Salta, Santiago, etc)
         *
         * Tipo de régimen de retención:
         * RG (ATP Chaco) 2037/2020 Régimen de retención del impuesto sobre los ingresos brutos aplicable a operaciones realizadas a través de sitios web
         *
         * Jurisdicción: siempre “906”
         *
         * En el comprobante: "RG (ATP Chaco) 2037/2020”
         *
         * Frecuencia de declaración: MENSUAL.
         */
        $this->addSql("
            INSERT INTO `province_withholding_tax_setting` (
                `province_id`, `withholding_tax_type`, `withholding_tax_system`, `type`, `code`,
                `agent_subsidiary`, `last_certificate`, `period`, `min_amount`, `resolution`, `number`
            ) VALUES (
                :chacoProvinceId, 'TAX', 'SIRCAR', '001', '906',
                NULL, 0, 'MONTHLY', 0.00, 'RG (ATP Chaco) 2037/2020', 'XX-XXXXXXXX-X'
            );
            ", [
                'chacoProvinceId' => $chacoProvinceId,
            ]
        );

        /*
         * La Base del calculo se realiza sobre el Valor Bruto
         */
        $this->addSql("
            INSERT INTO `withholding_tax_rule` (
                `type`, `tax_category_id`, `province_id`, `unpublish_rate`, `minimum_amount`, `calculation_basis`,
                `withhold_occasional`, `has_tax_registry`, `period`, `download_date_db`, `modified_at`, `created_at`, `rate`
            ) VALUES (
                'TAX', NULL, :chacoProvinceId, 0, 0, 'GROSS',
                1, 0, 'This Month', NULL, NULL, NOW(), 0
            );
            ", [
                'chacoProvinceId' => $chacoProvinceId,
            ]
        );

        /*
         * Mínimo no sujeto a retención:
         * No cuenta con un monto mínimo sujeto a retención para los inscriptos,
         * solo cuenta con un monto mínimo de $20.000 para los sujetos no inscriptos en la regla de habitualidad.
         *
         * Reglas Simples.
         * Inscripto Local: 2,5%
         * Convenio Multilateral: 1,5%
         * Especial (Régimen Simplificado): 0%
         * Exento: 0%
         */
        $this->addSql("INSERT INTO `withholding_tax_simple_rule` (
            `type`, `province_id`, `tax_category_id`, `rate`, `minimum_amount`, `created_at`,
            `classification_id`, `tax_condition_id`, `income_tax`, `payment_method_type`
            ) VALUES (
                'TAX', :chacoProvinceId, 1, 2.50, 0.00, NOW(), NULL, NULL, NULL, NULL
            ), (
                'TAX', :chacoProvinceId, 2, 1.50, 0.00, NOW(), NULL, NULL, NULL, NULL
            ), (
                'TAX', :chacoProvinceId, 4, 0.00, 0.00, NOW(), NULL, NULL, NULL, NULL
            ), (
                'TAX', :chacoProvinceId, 5, 0.00, 0.00, NOW(), NULL, NULL, NULL, NULL
            );
            ", [
                'chacoProvinceId' => $chacoProvinceId,
            ]
        );

        /*
         * No inscriptos que cumplan con la regla de habitualidad
         * (5 o más operaciones a lo largo de un mes y que igualen o superen el importe de $20.000): 3,5%
         * cuenta con un monto mínimo de $20.000 para los sujetos no inscriptos en la regla de habitualidad.
         */
        $this->addSql("
            INSERT INTO `withholding_tax_hard_rule` (
                `withholding_tax_rule_id`, `rate`, `rule`, `verification_date`,
                `created_at`, `modified_at`, `minimum_amount`
            ) VALUES (
                 (select max(id) from withholding_tax_rule where province_id = :chacoProvinceId),
                 3.5,
                 '[{\"type\":\"SELECT\",\"field\":\"count\",\"fieldFunction\":\"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1) WHEN type_id = \'REFUND\' THEN (-1) ELSE 1 END)\",\"condition\":\">=\",\"value\":\"5\"},{\"type\":\"SELECT\",\"field\":\"amount\",\"fieldFunction\":\"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1 * _t.amount) WHEN type_id = \'REFUND\' THEN (-1 * _t.amount) ELSE _t.amount END)\",\"condition\":\">=\",\"value\":\"20000\"},{\"type\":\"WHERE\",\"field\":\"_t.created_at\",\"condition\":\">=\"}, {\"type\":\"WHERE\",\"field\":\"_t.status\",\"condition\":\"=\",\"value\":\"APPROVED\"} ]',
                 NULL,
                 NOW(),
                 NULL,
                 20000
            );
            ", [
                'chacoProvinceId' => $chacoProvinceId,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
