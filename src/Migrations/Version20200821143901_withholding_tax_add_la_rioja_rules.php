<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200821143901_withholding_tax_add_la_rioja_rules extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $laRiojaId = 46;

        $this->addSql("INSERT INTO `province_withholding_tax_setting` (`province_id`, `withholding_tax_type`, `withholding_tax_system`, 
                                `type`, `code`, `agent_subsidiary`, `last_certificate`, `period`, `min_amount`, `resolution`, `number`) 
                            VALUES 
                              (:laRiojaId,'TAX','SIRCAR','001','912',NULL,0,'MONTHLY',5000.00,'RG (DGIP La Rioja) 16/2020','XX-XXXXXXXX-X');",
            [
                'laRiojaId' => $laRiojaId,
            ]);

        $this->addSql("INSERT INTO `withholding_tax_rule` 
            (`type`, `tax_category_id`, `province_id`, `unpublish_rate`, `minimum_amount`, `calculation_basis`, `withhold_occasional`, 
             `has_tax_registry`, `period`, `download_date_db`, `modified_at`, `created_at`, `rate`) 
             VALUES 
                ('TAX',1,:laRiojaId,0,0,'GROSS',1,0,'This Month',NULL,NULL,NOW(),0);",
            [
                'laRiojaId' => $laRiojaId,
            ]);

        $this->addSql("INSERT INTO `withholding_tax_simple_rule` 
            (`type`, `province_id`, `tax_category_id`, `rate`, `minimum_amount`, `created_at`, `classification_id`, `tax_condition_id`, 
             `income_tax`, `payment_method_type`,`taxable_amount_coefficient` ) 
             VALUES 
                ('TAX',:laRiojaId,1,2.5,5000.00,NOW(),NULL,NULL,NULL,NULL,1),
                ('TAX',:laRiojaId,2,2.5,5000.00,NOW(),NULL,NULL,NULL,NULL,0.5),
                ('TAX',:laRiojaId,4,2.5,5000.00,NOW(),NULL,NULL,NULL,NULL,0.8),
                ('TAX',:laRiojaId,5,0.00,0.00,NOW(),NULL,NULL,NULL,NULL,1);",
            [
                'laRiojaId' => $laRiojaId,
            ]);

        $this->addSql("INSERT INTO `withholding_tax_hard_rule` 
            (`withholding_tax_rule_id`, `rate`, `rule`, `verification_date`, `created_at`, `modified_at`, `minimum_amount`) 
            VALUES
                (
                 (select max(id) from withholding_tax_rule where province_id = :laRiojaId),
                 5,
                 '[{\"type\":\"SELECT\",\"field\":\"count\",\"fieldFunction\":\"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1) WHEN type_id = \'REFUND\' THEN (-1) ELSE 1 END)\",\"condition\":\">=\",\"value\":\"3\"},{\"type\":\"SELECT\",\"field\":\"amount\",\"fieldFunction\":\"SUM(CASE WHEN type_id = \'ADJUSTMENT_DEBIT\' THEN (-1 * _t.amount) WHEN type_id = \'REFUND\' THEN (-1 * _t.amount) ELSE _t.amount END)\",\"condition\":\">=\",\"value\":\"5000\"},{\"type\":\"WHERE\",\"field\":\"_t.created_at\",\"condition\":\">=\"}, {\"type\":\"WHERE\",\"field\":\"_t.status\",\"condition\":\"=\",\"value\":\"APPROVED\"} ]',
                 NULL,
                 NOW(),
                 NULL,
                 5000
                );",
            [
                'laRiojaId' => $laRiojaId,
            ]);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
