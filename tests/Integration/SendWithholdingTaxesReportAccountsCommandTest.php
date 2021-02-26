<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Address;
use GeoPagos\ApiBundle\Entity\Country;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\User;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Command\SendWithholdingTaxesReportAccountsCommand;
use GeoPagos\WithholdingTaxBundle\Entity\Certificate;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Entity\SirtacDeclaration;
use GeoPagos\WithholdingTaxBundle\Entity\TaxConcept;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxStatus;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Services\WithholdStage\IibbWithholdStage;
use GeoPagos\WithholdingTaxBundle\Services\WithholdStage\IvaWithholdStage;

class SendWithholdingTaxesReportAccountsCommandTest extends TestCase
{
    use FactoriesTrait;

    private const COUNTRY_ID = 10;
    private const BUENOS_AIRES = 6;
    private const MENDOZA = 50;
    private const LA_RIOJA = 46;
    private const INSCRIPTO_LOCAL = 1;

    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var \League\Flysystem\Filesystem
     */
    private $fileSystem;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->loadFactories(self::$container->get('doctrine')->getManager());

        Carbon::setTestNow();

        $this->em = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->fileSystem = self::$container->get('public_filesystem');
    }

    public function provincesCasesDataProvider()
    {
        return [
            [
                'subsidiaryProvinceId' => self::BUENOS_AIRES,
                'withholdingTaxProvinceIds' => [
                    self::BUENOS_AIRES,
                ],
            ],
            [
                'subsidiaryProvinceId' => self::BUENOS_AIRES,
                'withholdingTaxProvinceId' => [
                    self::BUENOS_AIRES,
                    self::MENDOZA,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provincesCasesDataProvider
     */
    public function it_should_set_status_sent_when_run_command(
        int $subsidiaryProvinceId,
        array $withholdingTaxProvinceIds
    ): void {
        $account = $this->factory->create(Account::class,
            ['owner' => $this->factory->create(User::class)]
        );

        $subsidiaryProvince = $this->em->getRepository(Province::class)->find($subsidiaryProvinceId);

        $address = $this->factory->create(Address::class, [
            'country' => $this->em->getRepository(Country::class)->find(self::COUNTRY_ID),
            'province' => $subsidiaryProvince,
        ]);

        $subsidiary = $this->factory->create(Subsidiary::class, [
            'account' => $account,
            'address' => $address,
            'taxCategory' => $this->em->getRepository(TaxCategory::class)->find(self::INSCRIPTO_LOCAL),
        ]);

        foreach ($withholdingTaxProvinceIds as $withholdingTaxProvinceId) {
            $withholdingTaxProvince = $this->em->getRepository(Province::class)->find($withholdingTaxProvinceId);
            $this->factory->create(WithholdingTax::class, [
                'subsidiary' => $subsidiary,
                'date' => Carbon::now()->startOfMonth()->subMonths(3),
                'province' => $withholdingTaxProvince,
            ]);

            $this->factory->create(WithholdingTax::class, [
                'subsidiary' => $subsidiary,
                'date' => Carbon::now()->startOfMonth()->subMonths(2),
                'province' => $withholdingTaxProvince,
            ]);

            $this->factory->create(WithholdingTax::class, [
                'subsidiary' => $subsidiary,
                'date' => Carbon::now()->startOfMonth()->subMonth(),
                'province' => $withholdingTaxProvince,
            ]);

            $this->factory->create(WithholdingTax::class, [
                'subsidiary' => $subsidiary,
                'date' => Carbon::now()->startOfMonth(),
                'province' => $withholdingTaxProvince,
            ]);
        }

        $month = Carbon::now()->format('Ym');

        $this->runCommandWithParameters(SendWithholdingTaxesReportAccountsCommand::NAME, [
            '--include-old-certificates' => 'true',
        ]);

        $withholdingTaxesSent = $this->em
            ->getRepository(WithholdingTax::class)
            ->findWithActiveSubsidiaryBy(
                $month,
                true,
                [
                    'status' => WithholdingTaxStatus::SENT,
                ]
            );

        $this->assertCount(3 * count($withholdingTaxProvinceIds), $withholdingTaxesSent);

        $withholdingTaxesCreated = $this->em
            ->getRepository(WithholdingTax::class)
            ->findWithActiveSubsidiaryBy(
                $month,
                true,
                [
                    'status' => WithholdingTaxStatus::CREATED,
                ]
            );

        $this->assertCount(1 * count($withholdingTaxProvinceIds), $withholdingTaxesCreated);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearDirectoryForTest('./tests/../storage/public/certificate/');
    }

    /**
     * @test
     */
    public function set_to_sent_iva_certificates(): void
    {
        $subsidiary = $this->factory->create(Subsidiary::class, [
            'taxCategory' => $this->em->getRepository(TaxCategory::class)->find(self::INSCRIPTO_LOCAL),
            'account' => $this->factory->create(Account::class, [
                'owner' => $this->factory->create(User::class),
            ]),
        ]);
        /* Rows to the cert. */
        $withholdingTaxes = $this->factory->seed(5, WithholdingTax::class, [
            'subsidiary' => $subsidiary,
            'date' => Carbon::now()->startOfMonth()->startOfDay(),
            'type' => IvaWithholdStage::getTaxType(),
        ]);
        /*  end rows into the cert */
        /* other rows outside the cert */
        $this->factory->seed(5, WithholdingTax::class, [
            'subsidiary' => $subsidiary,
            'date' => Carbon::now()->subMonth(),
            'type' => IvaWithholdStage::getTaxType(),
        ]);
        $this->factory->seed(5, WithholdingTax::class, [
            'subsidiary' => $subsidiary,
            'date' => Carbon::now()->subMonth(),
            'type' => IibbWithholdStage::getTaxType(),
        ]);

        /*  end rows outside  the cert */

        $month = Carbon::now()->startOfMonth()->format('Ym');

        $this->runCommandWithParameters(SendWithholdingTaxesReportAccountsCommand::NAME, [
            '--month' => $month,
        ]);
        $withholdingTaxes = $this->em
            ->getRepository(WithholdingTax::class)
            ->findBy(['status' => WithholdingTaxStatus::SENT, 'type' => IvaWithholdStage::getTaxType()]);
        $this->assertCount(5, $withholdingTaxes);
        /* @var $firstWitholdingTax WithholdingTax */
        $firstWitholdingTax = $withholdingTaxes[0];
        $certificates = $this->em->getRepository(Certificate::class)->findBy([
            'type' => $firstWitholdingTax->getType(),
            'status' => $firstWitholdingTax->getStatus(),
            'id' => $firstWitholdingTax->getCertificate()->getId(),
        ]);
        $this->assertCount(1, $certificates);
    }

    /**
     * @test
     */
    public function set_to_sent_iibb_sirtac_certificates(): void
    {
        $subsidiary = $this->factory->create(Subsidiary::class, [
            'taxCategory' => $this->em->getRepository(TaxCategory::class)->find(self::INSCRIPTO_LOCAL),
            'account' => $this->factory->create(Account::class, [
                'owner' => $this->factory->create(User::class),
            ]),
        ]);
        /* Rows to the cert. */
        /* @var $province Province */
        $province = $this->factory->create(Province::class);
        $provinceSetting = $this->factory->create(ProvinceWithholdingTaxSetting::class, [
            'province' => $province,
            'withholdingTaxType' => WithholdingTaxTypeEnum::SIRTAC,
        ]);
        $province->setProvinceWithholdingTaxSetting($provinceSetting);
        $this->em->flush();
        $taxConcept1 = $this->factory->create(TaxConcept::class);
        $taxConcept2 = $this->factory->create(TaxConcept::class);
        $withholdingTaxes = $this->factory->seed(5, SirtacDeclaration::class, [
            'subsidiary' => $subsidiary,
            'province' => $province,
            'taxConcept' => $taxConcept1,
        ]);
        $withholdingTaxes = $this->factory->seed(5, SirtacDeclaration::class, [
            'subsidiary' => $subsidiary,
            'province' => $province,
            'taxConcept' => $taxConcept2,
        ]);
        /*  end rows into the cert */
        $month = Carbon::now()->startOfMonth()->format('Ym');

        $this->runCommandWithParameters(SendWithholdingTaxesReportAccountsCommand::NAME, [
            '--month' => $month,
        ]);
        $withholdingTaxes = $this->em
            ->getRepository(SirtacDeclaration::class)
            ->findBy(['status' => WithholdingTaxStatus::SENT]);
        $this->assertCount(10, $withholdingTaxes);
        /* @var $firstWitholdingTax WithholdingTax */
        $firstWitholdingTax = $withholdingTaxes[0];
        $certificates = $this->em->getRepository(Certificate::class)->findBy([
            'type' => $firstWitholdingTax->getType(),
            'status' => $firstWitholdingTax->getStatus(),
        ]);
        $this->assertCount(1, $certificates);
        $certificate = $certificates[0];
        /* @var $certificate Certificate */
        $certificateData = $this->fileSystem->read($certificate->getFileName());
    }
}
