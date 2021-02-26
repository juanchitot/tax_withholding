<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201201192335_withholding_tax_add_chubut_rules extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $chubutProvinceId = 26;

        /*
         * Diseño del archivo de declaración: SIRCAR
         * El diseño es el SIRCAR (El modelo original, el que sale para Salta, Santiago, etc)
         *
         * Tipo de régimen de retención:
         * R. (DGR Chubut) 435/2020
         * Régimen de retención del impuesto sobre los ingresos brutos aplicable a operaciones perfeccionadas electrónicamente
         *
         * Jurisdicción: siempre “907”
         *
         * En el comprobante: “: R. (DGR Chubut) 435/2020”
         *
         * Frecuencia de declaración: MENSUAL.
         */
        $this->addSql("
            INSERT INTO `province_withholding_tax_setting` (
                `province_id`, `withholding_tax_type`, `withholding_tax_system`, `type`, `code`,
                `agent_subsidiary`, `last_certificate`, `period`, `min_amount`, `resolution`, `number`
            ) VALUES (
                :chubutProvinceId, 'TAX', 'SIRCAR', '001', '907',
                NULL, 0, 'MONTHLY', 0.00, 'R. (DGR Chubut) 435/2020', 'XX-XXXXXXXX-X'
            );
            ", [
                'chubutProvinceId' => $chubutProvinceId,
            ]
        );

        /*
         * La Base del calculo se realiza sobre el Valor Bruto
         */
        $this->addSql("
            INSERT INTO `withholding_tax_rule` (
                `type`, `tax_category_id`, `province_id`, `unpublish_rate`, `minimum_amount`,
                `calculation_basis`, `withhold_occasional`, `has_tax_registry`, `period`,
                `download_date_db`, `modified_at`, `created_at`, `rate`
            ) VALUES (
                'TAX', NULL, :chubutProvinceId, 0, 0,
                'GROSS', 1, 0, 'This Month',
                NULL, NULL, NOW(), 0
            );
            ", [
                'chubutProvinceId' => $chubutProvinceId,
            ]
        );

        /*
         * Mínimo no sujeto a retención: El monto mínimo sujeto a retención en Chubut
         * es de $7.500 tanto para los inscriptos como para los no inscriptos.
         *
         * Reglas Simples.
         * Inscripto Local: 0%
         * Convenio Multilateral: 2%
         * Especial (Régimen Simplificado): 0%
         * Exento: 0%
         */
        $this->addSql("
            INSERT INTO `withholding_tax_simple_rule` (
                `type`, `province_id`, `tax_category_id`, `rate`, `minimum_amount`, `created_at`,
                `classification_id`, `tax_condition_id`, `income_tax`, `payment_method_type`
            ) VALUES (
                'TAX', :chubutProvinceId, 1, 0.00, 7500, NOW(), NULL, NULL, NULL, NULL
            ), (
                'TAX', :chubutProvinceId, 2, 2.00, 7500, NOW(), NULL, NULL, NULL, NULL
            ), (
                'TAX', :chubutProvinceId, 4, 0.00, 7500, NOW(), NULL, NULL, NULL, NULL
            ), (
                'TAX', :chubutProvinceId, 5, 0.00, 7500, NOW(), NULL, NULL, NULL, NULL
            );
            ", [
                'chubutProvinceId' => $chubutProvinceId,
            ]
        );

        /*
         * Regla de Habilidad: No Inscriptos que cumplan con la regla de habitualidad
         * (Más de 3 operaciones en un año y superen el importe de $7.500): 3%
         */
        $this->addSql("
            INSERT INTO `withholding_tax_hard_rule` (
                `withholding_tax_rule_id`, `rate`, `rule`, `verification_date`,
                `created_at`, `modified_at`, `minimum_amount`
            ) VALUES (
                 (select max(id) from withholding_tax_rule where province_id = :chubutProvinceId),
                 3,
                 '[{\"type\":\"SELECT\",\"field\":\"count\",\"fieldFunction\":\"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1) WHEN type_id = \'REFUND\' THEN (-1) ELSE 1 END)\",\"condition\":\">=\",\"value\":\"3\"},{\"type\":\"SELECT\",\"field\":\"amount\",\"fieldFunction\":\"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1 * _t.amount) WHEN type_id = \'REFUND\' THEN (-1 * _t.amount) ELSE _t.amount END)\",\"condition\":\">=\",\"value\":\"7500\"},{\"type\":\"WHERE\",\"field\":\"_t.created_at\",\"condition\":\">=\"}, {\"type\":\"WHERE\",\"field\":\"_t.status\",\"condition\":\"=\",\"value\":\"APPROVED\"} ]',
                 NULL,
                 NOW(),
                 NULL,
                 7500
            );
            ", [
                'chubutProvinceId' => $chubutProvinceId,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
