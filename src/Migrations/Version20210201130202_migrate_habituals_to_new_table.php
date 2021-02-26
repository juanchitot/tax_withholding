<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210201130202_migrate_habituals_to_new_table extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('
            INSERT INTO habituals (tax_type, subsidiary_id, province_id, since)

            SELECT \'VAT\', subsidiary_id, NULL, vat_last_withheld
            FROM subsidiary_withheld_taxes
            WHERE vat_last_withheld IS NOT NULL

            UNION

            SELECT \'INCOME_TAX\', subsidiary_id, NULL, earnings_tax_last_withheld
            FROM subsidiary_withheld_taxes
            WHERE earnings_tax_last_withheld IS NOT NULL

            UNION

            SELECT \'TAX\', swt.subsidiary_id, p.id, swt.gross_income_tax_last_withheld
            FROM subsidiary_withheld_taxes swt
                INNER JOIN subsidiary s on swt.subsidiary_id = s.id
                INNER JOIN address a on s.address_id = a.id
                INNER JOIN province p on a.province_id = p.id
            WHERE swt.gross_income_tax_last_withheld IS NOT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('TRUNCATE TABLE habituals');
    }
}
