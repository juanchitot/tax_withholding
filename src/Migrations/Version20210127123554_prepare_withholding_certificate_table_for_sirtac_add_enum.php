<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210127123554_prepare_withholding_certificate_table_for_sirtac_add_enum extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE withholding_tax_certificate change type type enum('TAX','VAT','INCOME_TAX','SIRTAC');
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE withholding_tax_certificate change type type enum('TAX','VAT','INCOME_TAX');
        ");
    }
}
