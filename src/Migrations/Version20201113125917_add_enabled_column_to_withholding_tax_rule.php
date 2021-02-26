<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201113125917_add_enabled_column_to_withholding_tax_rule extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE withholding_tax_rule 
                ADD is_enabled TINYINT(1) DEFAULT \'0\' NOT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE withholding_tax_rule
                DROP is_enabled
        ');
    }
}
