<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200929182645_migrate_withholding_tax_certificate extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'this migration copy all the data within withholding_tax  table to new table withholding_tax_certificate ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT IGNORE INTO withholding_tax_certificate (subsidiary_id, province_id, fileName, date_period, type, status)
                    SELECT subsidiary_id, province_id, file,  date, type, status FROM withholding_tax where file is not null');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM withholding_tax_certificate');
    }
}
