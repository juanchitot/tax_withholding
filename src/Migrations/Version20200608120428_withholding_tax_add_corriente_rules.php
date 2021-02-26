<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200608120428_withholding_tax_add_corriente_rules extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $corrientesId = 18;

        $this->addSql("INSERT INTO `province_withholding_tax_setting` (`province_id`, `withholding_tax_type`, `withholding_tax_system`, 
                                `type`, `code`, `agent_subsidiary`, `last_certificate`, `period`, `min_amount`, `resolution`, `number`) 
                            VALUES 
                              (:corrientesId,'TAX','SIRCAR','002','905',NULL,0,'MONTHLY',500.00,'RG (DGR Corrientes) 21/2020','XX-XXXXXXXX-X');",
        [
            'corrientesId' => $corrientesId,
        ]);

        $this->addSql("INSERT INTO `withholding_tax_rule` 
            (`type`, `tax_category_id`, `province_id`, `unpublish_rate`, `minimum_amount`, `calculation_basis`, `withhold_occasional`, 
             `has_tax_registry`, `period`, `download_date_db`, `modified_at`, `created_at`, `rate`) 
             VALUES 
                ('TAX',1,:corrientesId,0,0,'GROSS',1,0,'This Month',NULL,NULL,NOW(),0);",
            [
                'corrientesId' => $corrientesId,
            ]);

        $this->addSql("INSERT INTO `withholding_tax_simple_rule` 
            (`type`, `province_id`, `tax_category_id`, `rate`, `minimum_amount`, `created_at`, `classification_id`, `tax_condition_id`, 
             `income_tax`, `payment_method_type`) 
             VALUES 
                ('TAX',:corrientesId,1,2,500.00,NOW(),NULL,NULL,NULL,NULL),
                ('TAX',:corrientesId,2,2,500.00,NOW(),NULL,NULL,NULL,NULL),
                ('TAX',:corrientesId,4,0.00,0.00,NOW(),NULL,NULL,NULL,NULL),
                ('TAX',:corrientesId,5,0.00,0.00,NOW(),NULL,NULL,NULL,NULL);",
            [
                'corrientesId' => $corrientesId,
            ]);

        $this->addSql("INSERT INTO `withholding_tax_hard_rule` 
            (`withholding_tax_rule_id`, `rate`, `rule`, `verification_date`, `created_at`, `modified_at`, `minimum_amount`) 
            VALUES
                (
                 (select max(id) from withholding_tax_rule where province_id = 18),
                 2.50,
                 '[{\"type\":\"SELECT\",\"field\":\"count\",\"fieldFunction\":\"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1) WHEN type_id = \'REFUND\' THEN (-1) ELSE 1 END)\",\"condition\":\">=\",\"value\":\"10\"},{\"type\":\"SELECT\",\"field\":\"amount\",\"fieldFunction\":\"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1 * _t.amount) WHEN type_id = \'REFUND\' THEN (-1 * _t.amount) ELSE _t.amount END)\",\"condition\":\">=\",\"value\":\"20000\"},{\"type\":\"WHERE\",\"field\":\"_t.created_at\",\"condition\":\">=\"}, {\"type\":\"WHERE\",\"field\":\"_t.status\",\"condition\":\"=\",\"value\":\"APPROVED\"} ]',
                 NULL,
                 NOW(),
                 NULL,
                 500
                );");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
