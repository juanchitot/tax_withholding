<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210129130202_fill_settlement_date_of_withholding_tax_detail extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fills settlementDate Field on WithholdingTaxDetail table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("update `withholding_tax_detail`  as d
                    inner join `transaction`  as t on t.id = d.transaction_id
                    set
                        d.settlement_date  = if(d.withholded_at>CONVERT_TZ(t.available_date,'UTC','-03:00'), d.withholded_at, CONVERT_TZ(t.available_date,'UTC','-03:00')) ;");
    }

    public function down(Schema $schema): void
    {
    }
}
