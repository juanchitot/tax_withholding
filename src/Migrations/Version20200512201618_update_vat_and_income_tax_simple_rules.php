<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200512201618_update_vat_and_income_tax_simple_rules extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update VAT and Income Tax simple rules';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('insert into withholding_tax_simple_rule (
                            type, rate, created_at, tax_condition_id, income_tax, payment_method_type
                        ) values
                                 ( \'VAT\',0,NOW(),2,null, \'DEBIT\' ),
                                 ( \'VAT\',0,NOW(),2,null, \'CREDIT\' ),
                        
                                 ( \'INCOME_TAX\',2,NOW(),4, null, \'DEBIT\' ),
                                 ( \'INCOME_TAX\',2,NOW(),4, null, \'CREDIT\' );');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
