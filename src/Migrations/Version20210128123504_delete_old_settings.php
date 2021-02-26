<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210128123504_delete_old_settings extends AbstractMigration
{
    const PROVINCES = [
        66, // Salta
        86, // Santiago del Estero,
        78, // Santa Cruz
        14, // Córdoba
        38, // Jujuy
        46, // La Rioja
        94, // Tierra del Fuego
        22, // Chaco
    ];

    public function up(Schema $schema): void
    {
        $this->addSql('
            DELETE FROM
                province_withholding_tax_setting
            WHERE
                withholding_tax_type = \'TAX\' AND
                province_id IN ('.implode(',', self::PROVINCES).')
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
