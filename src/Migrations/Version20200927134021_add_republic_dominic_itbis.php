<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20200927134021_add_republic_dominic_itbis extends AbstractMigration
{
    const DO_COUNTRY_ID = 57;

    protected $classifications = [
        '7997' => 'Clubes de membresía (deportes, recreación, atletismo), clubes de campo y campos de golf privados',
        '8220' => 'Colegios, Universidades, Escuelas Profesionales y Colegios Junior',
        '8021' => 'Dentistas y Ortodoncistas',
        '7941' => 'Deportes comerciales, clubes deportivos profesionales, campos deportivos',
        '8011' => 'Doctores y Medicos',
        '8241' => 'Escuelas de correspondencia',
        '8244' => 'Escuelas de negocios y secretariado',
        '8211' => 'Escuelas primarias y secundarias',
        '5541' => 'Estaciones de servicio',
        '5912' => 'Farmacias',
        '8062' => 'Hospitales',
        '8050' => 'Instalaciones de enfermería y cuidado personal',
        '8071' => 'Laboratorios Médicos y Dentales',
        '8042' => 'Optometristas y Oftalmólogos',
        '8398' => 'Organizaciones de servicio social caritativo',
        '8031' => 'Osteópatas',
        '8049' => 'Podólogos y quiroprácticos',
        '8041' => 'Quiroprácticos',
        '7230' => 'Salones de Belleza y Barberias',
        '7297' => 'Salones de masaje',
        '8351' => 'Servicios de cuidado infantil',
        '4789' => 'Servicios de transporte (no clasificados en otra parte)',
        '7261' => 'Servicios funerarios y crematorios',
        '9399' => 'Servicios gubernamentales',
        '8099' => 'Servicios médicos y profesionales de la salud',
        '4900' => 'Servicios públicos: electricidad, gas, agua y sanitarios',
        '7298' => 'Spas de salud y belleza',
        '5943' => 'Tiendas de papelería, tiendas de oficina y útiles escolares',
        '5451' => 'Tiendas de productos diarios',
        '4111' => 'Transporte local y suburbano de pasajeros suburbanos, incluidos ferries',
    ];

    public function getDescription(): string
    {
        return 'this migration add the rule for ITBIS for Dominic Republic, [IT HAS TO BE APPLIED IN MIO STRICTLY]';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO `province_withholding_tax_setting` (province_id, withholding_tax_type, withholding_tax_system, last_certificate, period ) '.
            " values(0,'ITBIS','',0,'MONTHLY');");

        $this->addSql("INSERT INTO `withholding_tax_rule` ( `type`, `tax_category_id`, `province_id`, `unpublish_rate`, `minimum_amount`, 
                                    `calculation_basis`, `withhold_occasional`, `has_tax_registry`, `period`, 
                                    `download_date_db`, `modified_at`, `created_at`, `rate`) 
                              VALUES 
                                 ('ITBIS',NULL,NULL,0.00,0.00,'GROSS',0,0,'This Month',NULL,NULL,NOW(),0.000) ;");

        $this->insertItbisTaxedClassifications();
    }

    /**
     * {@inheritdoc}
     */
    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM `withholding_tax_rule` where type ='ITBIS'");
        $this->addSql("DELETE FROM `withholding_tax_simple_rule` where type ='ITBIS'");
    }

    protected function insertItbisTaxedClassifications()
    {
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        foreach ($this->classifications as $code => $name) {
            $classificationId = $this->getClassificationID($code);

            $this->addSql("INSERT INTO `withholding_tax_simple_rule` ( `type`, `province_id`, `tax_category_id`, `rate`, `minimum_amount`, 
                           `created_at`, `classification_id`, `tax_condition_id`, `income_tax`, `payment_method_type`) 
                           VALUES  ('ITBIS',NULL,NULL,2.0,0,NOW(),$classificationId,NULL,NULL,NULL)");
        }
    }

    private function getClassificationID($code): int
    {
        return $this->connection->fetchColumn("SELECT id from classification where code = '$code'ORDER BY id DESC LIMIT 1");
    }
}
