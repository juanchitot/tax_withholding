<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20200628163638_remove_unique_constraint_in_withholding_log extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            alter table withholding_tax_log drop FOREIGN KEY FK_E3B3F2212FC0CB0F;
            alter table withholding_tax_log drop INDEX UNIQ_E3B3F2212FC0CB0F;
            ');
    }

    public function down(Schema $schema): void
    {
        return;
    }
}
