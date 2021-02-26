<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200530190600_remove_payment_tax_specific_fields_from_withholding_tax extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes unnecessary extra fields added to withholding_tax for credit/debit withholding taxes in each row';
    }

    public function up(Schema $schema): void
    {
        $this->addSQL('ALTER TABLE  `withholding_tax` 
                       DROP COLUMN  `credit_taxable_income`,
                       DROP COLUMN  `debit_taxable_income`,
                       DROP COLUMN  `credit_rate`,
                       DROP COLUMN  `debit_rate`,
                       DROP COLUMN  `credit_amount`,
                       DROP COLUMN  `debit_amount`
                  ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `withholding_tax` 
                      ADD COLUMN `credit_taxable_income` decimal(12,2) DEFAULT 0,
                      ADD COLUMN `debit_taxable_income` decimal(12,2) DEFAULT 0,
                      ADD COLUMN `credit_rate` decimal(4,2) DEFAULT 0,
                      ADD COLUMN `debit_rate` decimal(4,2) DEFAULT 0,
                      ADD COLUMN `credit_amount` decimal(12,2) DEFAULT 0,
                      ADD COLUMN `debit_amount` decimal(12,2) DEFAULT 0
                 ');
    }
}
