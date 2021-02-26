<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201201170040_withholding_tax_add_santa_cruz_rules extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $santaCruzId = 78;

        $this->addSql("
            INSERT INTO `province_withholding_tax_setting` (
                `province_id`, `withholding_tax_type`, `withholding_tax_system`, `type`, `code`,
                `agent_subsidiary`, `last_certificate`, `period`, `min_amount`, `resolution`, `number`
            ) VALUES (
                :santaCruzId, 'TAX', 'SIRCAR', '005', '912', NULL, 0, 'MONTHLY', 0.00, 'RG 021/2020', 'XX-XXXXXXXX-X'
            );
            ", [
                'santaCruzId' => $santaCruzId,
            ]
        );

        $this->addSql("
            INSERT INTO `withholding_tax_rule` (
                `type`, `tax_category_id`, `province_id`, `unpublish_rate`, `minimum_amount`, `calculation_basis`,
                `withhold_occasional`, `has_tax_registry`, `period`, `download_date_db`, `modified_at`, `created_at`, `rate`
            ) VALUES (
                'TAX', NULL, :santaCruzId, 0, 0, 'GROSS', 1, 1, 'This Month', NULL, NULL, NOW(), 0
            );
            ", [
                'santaCruzId' => $santaCruzId,
            ]
        );

        $this->addSql("
            INSERT INTO `withholding_tax_simple_rule` (
                `type`, `province_id`, `tax_category_id`, `rate`, `minimum_amount`, `created_at`,
                `classification_id`, `tax_condition_id`, `income_tax`, `payment_method_type`
            ) VALUES (
                'TAX', :santaCruzId, 4, 0.00, 0.00, NOW(), NULL, NULL, NULL, NULL
            ), (
                'TAX', :santaCruzId, 5, 0.00, 0.00, NOW(), NULL, NULL, NULL, NULL
            );
            ", [
                'santaCruzId' => $santaCruzId,
            ]
        );

        $this->addSql("INSERT INTO `withholding_tax_hard_rule`
            (`withholding_tax_rule_id`, `rate`, `rule`, `verification_date`, `created_at`, `modified_at`, `minimum_amount`)
            VALUES
                (
                 (select max(id) from withholding_tax_rule where province_id = :santaCruzId),
                 3,
                 '[{\"type\":\"SELECT\",\"field\":\"count\",\"fieldFunction\":\"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1) WHEN type_id = \'REFUND\' THEN (-1) ELSE 1 END)\",\"condition\":\">=\",\"value\":\"5\"},{\"type\":\"SELECT\",\"field\":\"amount\",\"fieldFunction\":\"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1 * _t.amount) WHEN type_id = \'REFUND\' THEN (-1 * _t.amount) ELSE _t.amount END)\",\"condition\":\">=\",\"value\":\"12500\"},{\"type\":\"WHERE\",\"field\":\"_t.created_at\",\"condition\":\">=\"}, {\"type\":\"WHERE\",\"field\":\"_t.status\",\"condition\":\"=\",\"value\":\"APPROVED\"} ]',
                 NULL,
                 NOW(),
                 NULL,
                 12500
                );",
            [
                'santaCruzId' => $santaCruzId,
            ]);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
