<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210127123554_prepare_withholding_certificate_table_for_sirtac_remove_index extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            alter table withholding_tax_certificate  add key  FK_580E964CRR46114A (subsidiary_id),
            drop index subsidiary_province_certificate_date_idx
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE withholding_tax_certificate
            add unique 'subsidiary_province_certificate_date_idx' (subsidiary_id, province_id, date_period),
            drop key FK_580E964CRR46114A
        ");
    }
}
