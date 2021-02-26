<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200528230600_add_rate_to_withholding_tax_detail extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax_detail ADD COLUMN `rate` decimal (10,2) default 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax_detail DROP COLUMN `rate`');
    }
}
