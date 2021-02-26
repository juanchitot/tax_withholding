<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200610205028_withholding_tax_set_certificate_number_to_int extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax MODIFY COLUMN certificate_number BIGINT DEFAULT NULL;');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax MODIFY COLUMN certificate_number varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL;');
    }
}
