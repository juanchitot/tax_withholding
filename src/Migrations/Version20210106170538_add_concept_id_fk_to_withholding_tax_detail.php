<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210106170538_add_concept_id_fk_to_withholding_tax_detail extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE withholding_tax_detail
                ADD CONSTRAINT wtd_tax_concept_fk FOREIGN KEY (concept_id) REFERENCES tax_concept (id);
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE withholding_tax_detail DROP FOREIGN KEY wtd_tax_concept_fk;
        ');
    }
}
