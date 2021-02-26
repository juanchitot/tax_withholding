<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200511151319_update_salta_income_tax_withholding_tax_simple_rules extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE withholding_tax_simple_rule
                            SET rate = 3.6
                            , taxable_amount_coefficient = 0.5
                            WHERE tax_category_id = 2
                              and province_id = 66');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE withholding_tax_simple_rule
                            SET rate = 1.8
                            WHERE tax_category_id = 2
                              and province_id = 66');
    }
}
