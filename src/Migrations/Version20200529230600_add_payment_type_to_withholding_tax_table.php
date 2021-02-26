<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200529230600_add_payment_type_to_withholding_tax_table extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds payment_type column to withholding_tax to have one rOw per payment type (CREDIT | DEBIT)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `withholding_tax` ADD COLUMN `payment_type` varchar(10) DEFAULT \'ALL\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSQL('ALTER TABLE `withholding_tax` DROP COLUMN `payment_type`');
    }
}
