<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201228102350_fill_withholded_at_field extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fills withholdedAt Field on WithholdingTaxDetail table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('update `withholding_tax_detail`  as d  inner join `transaction`  as t on t.id = d.transaction_id  set d.withholded_at  = t.available_date ;');
    }

    public function down(Schema $schema): void
    {
    }
}
