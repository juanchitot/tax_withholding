<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20200513123743_deposit_update_currency_code extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update Deposit Currency Code <-- ONLY FOR ARS SITES';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('update deposit set currency_code = \'032\' where currency_code is null');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
