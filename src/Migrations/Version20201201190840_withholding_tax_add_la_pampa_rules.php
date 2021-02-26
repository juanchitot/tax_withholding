<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201201190840_withholding_tax_add_la_pampa_rules extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $laPampaProvinceId = 42;

        /*
         * Diseño del archivo de declaración: SIRCAR
         * El diseño es el SIRCAR (El modelo original, el que sale para Salta, Santiago, etc)
         *
         * Tipo de régimen de retención:
         * RG (DGR La Pampa) 54/2007 (Anexo IV). Régimen de retención Retenciones sobre tarjetas de compra,
         * débito, gestión de pagos y similares
         *
         * Jurisdicción: siempre “911”
         *
         * En el comprobante: Retención IIBB La Pampa RG 54/2007 (Anexo IV)
         *
         * Frecuencia de declaración: MENSUAL.
         */
        $this->addSql("
            INSERT INTO `province_withholding_tax_setting` (
                `province_id`, `withholding_tax_type`, `withholding_tax_system`, `type`, `code`,
                `agent_subsidiary`, `last_certificate`, `period`, `min_amount`, `resolution`, `number`
            ) VALUES (
                :laPampaProvinceId, 'TAX', 'SIRCAR', '001', '911',
                NULL, 0, 'MONTHLY', 0.00, 'RG (DGR La Pampa) 54/2007 (Anexo IV)', 'XX-XXXXXXXX-X'
            );
            ", [
                'laPampaProvinceId' => $laPampaProvinceId,
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
                'TAX', NULL, :laPampaProvinceId, 0, 0, 'GROSS',
                1, 0, 'This Month', NULL, NULL, NOW(), 0
            );
            ", [
                'laPampaProvinceId' => $laPampaProvinceId,
            ]
        );

        /*
         * Mínimo no sujeto a retención: NO tiene
         *
         * Reglas Simples.
         * Inscripto Local: 2.5%
         * Convenio Multilateral: 2.5%
         * Especial (Régimen Simplificado): 0%
         * Exento: 0%
         */
        $this->addSql("
            INSERT INTO `withholding_tax_simple_rule` (
                `type`, `province_id`, `tax_category_id`, `rate`, `minimum_amount`, `created_at`,
                `classification_id`, `tax_condition_id`, `income_tax`, `payment_method_type`
            ) VALUES (
                'TAX', :laPampaProvinceId, 1, 2.50, 0.00, NOW(), NULL, NULL, NULL, NULL
            ), (
                'TAX', :laPampaProvinceId, 2, 2.50, 0.00, NOW(), NULL, NULL, NULL, NULL
            ), (
                'TAX', :laPampaProvinceId, 4, 0.00, 0.00, NOW(), NULL, NULL, NULL, NULL
            ), (
                'TAX', :laPampaProvinceId, 5, 0.00, 0.00, NOW(), NULL, NULL, NULL, NULL
            );
            ", [
                'laPampaProvinceId' => $laPampaProvinceId,
            ]
        );

        /*
         * No inscriptos que cumplan con la regla de habitualidad (3 o más operaciones al mes): 2,5%
         * Mínimo no sujeto a retención: NO tiene
         */
        $this->addSql("
            INSERT INTO `withholding_tax_hard_rule` (
                `withholding_tax_rule_id`, `rate`, `rule`, `verification_date`,
                `created_at`, `modified_at`, `minimum_amount`
            ) VALUES (
                 (select max(id) from withholding_tax_rule where province_id = :laPampaProvinceId),
                 2.5,
                 '[{\"type\":\"SELECT\",\"field\":\"count\",\"fieldFunction\":\"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1) WHEN type_id = \'REFUND\' THEN (-1) ELSE 1 END)\",\"condition\":\">=\",\"value\":\"3\"},{\"type\":\"SELECT\",\"field\":\"amount\",\"fieldFunction\":\"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1 * _t.amount) WHEN type_id = \'REFUND\' THEN (-1 * _t.amount) ELSE _t.amount END)\",\"condition\":\">=\",\"value\":\"0\"},{\"type\":\"WHERE\",\"field\":\"_t.created_at\",\"condition\":\">=\"}, {\"type\":\"WHERE\",\"field\":\"_t.status\",\"condition\":\"=\",\"value\":\"APPROVED\"} ]',
                 NULL,
                 NOW(),
                 NULL,
                 0
            );
            ", [
                'laPampaProvinceId' => $laPampaProvinceId,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
