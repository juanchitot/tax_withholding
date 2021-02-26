<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20210105151023_enable_sirtac_rule extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            UPDATE withholding_tax_rule SET is_enabled = 1 WHERE type = \'SIRTAC\'
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            UPDATE withholding_tax_rule SET is_enabled = 0 WHERE type = \'SIRTAC\'
        ');
    }
}
