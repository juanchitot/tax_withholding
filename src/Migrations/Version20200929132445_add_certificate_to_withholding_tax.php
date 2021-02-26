<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200929132445_add_certificate_to_withholding_tax extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add certificate_id to withholding_tax ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax ADD certificate_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE withholding_tax ADD CONSTRAINT FK_FD77EE7C99223FFD FOREIGN KEY (certificate_id) REFERENCES withholding_tax_certificate (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE withholding_tax DROP certificate_id');
        $this->addSql('ALTER TABLE withholding_tax DROP FOREIGN KEY FK_FD77EE7C99223FFD');
    }
}
