<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201214152454_add_sirtac_to_subsidiary_withheld_taxes extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `subsidiary_withheld_taxes`
                ADD COLUMN `sirtac_tax_last_withheld` DATETIME NULL AFTER `gross_income_tax_last_withheld`;
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE `subsidiary_withheld_taxes` DROP COLUMN `sirtac_tax_last_withheld`;
        ');
    }
}
