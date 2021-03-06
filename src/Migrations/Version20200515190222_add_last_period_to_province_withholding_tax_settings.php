<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200515190222_add_last_period_to_province_withholding_tax_settings extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE province_withholding_tax_setting ADD COLUMN `last_period_start_date` datetime null');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE province_withholding_tax_setting DROP COLUMN `last_period_start_date`;');
    }
}
