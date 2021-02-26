<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200814180220_add_province_to_withholding_tax extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax ADD province_id int(11) default NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `withholding_tax_rule_file` DROP COLUMN `province_id`;');
    }
}
