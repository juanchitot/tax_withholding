<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Registries;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\BackOfficeAuthenticationTrait;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use GeoPagos\WithholdingTaxBundle\Form\Type\WithholdingTaxRegisterProvinceType;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class WithholdingTaxRegisterProvinceBackendSectionTest extends TestCase
{
    use BackOfficeAuthenticationTrait;
    use FactoriesTrait;

    private const BUENOS_AIRES = 6;
    private const INCOME_TAX_FILE = 'pba_test_registry.txt';
    private const REGISTRY_DIR = '/files';
    private const WITHHOLDING_TAX_REGISTRIES_PROVINCE_REGISTER = '/withholding-tax-registries/province-register/';

    /** @var FilesystemInterface */
    private $filesystem;
    /** @var EntityManagerInterface */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = self::$container->get('doctrine')->getManager();
        $this->filesystem = self::$container->get('local_filesystem');
    }

    /** @test */
    public function shows_uploaded_withholding_tax_register_province_section()
    {
        $this->loginIntoBackoffice($this->client);

        $this->client->request('GET', self::WITHHOLDING_TAX_REGISTRIES_PROVINCE_REGISTER);
        $this->assertResponseIsSuccessful();
    }

    /** @test * */
    public function it_creates_a_new_gross_income_register()
    {
        $this->loginIntoBackoffice($this->client);

        $uploadedFile = new UploadedFile(
            __DIR__.self::REGISTRY_DIR.DIRECTORY_SEPARATOR.self::INCOME_TAX_FILE,
            self::INCOME_TAX_FILE, null, false, true
        );

        $response = $this->client->request('POST', self::WITHHOLDING_TAX_REGISTRIES_PROVINCE_REGISTER.'new', [
            'withholding_tax_register_province' => [
                'origin' => WithholdingTaxRegisterProvinceType::PROVINCE_PREFIX.self::BUENOS_AIRES,
                'dbFile' => $uploadedFile,
            ], ], [
                $uploadedFile,
            ]
        );

        $grossIncomeFileRules = $this->entityManager
            ->getRepository(WithholdingTaxRuleFile::class)
            ->findBy([
                    'fileType' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
                ]
            );

        $this->assertCount(1, $grossIncomeFileRules);
    }

    /** @test * */
    public function it_can_delete_pending_registries()
    {
        $this->loginIntoBackoffice($this->client);

        $taxRegistry = $this->factory->create(WithholdingTaxRuleFile::class, [
            'province' => $this->entityManager->getRepository(Province::class)->find(self::BUENOS_AIRES),
            'fileType' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
        ]);

        $fileId = $taxRegistry->getId();

        $this->client->request('POST', self::WITHHOLDING_TAX_REGISTRIES_PROVINCE_REGISTER.$fileId.'/delete');

        $this->entityManager->clear();

        $taxRegistryFromDatabase = $this->entityManager->getRepository(WithholdingTaxRuleFile::class)->find($fileId);

        $this->assertNull($taxRegistryFromDatabase);
    }

    /** @test * */
    public function it_cant_delete_failed_registries()
    {
        $this->loginIntoBackoffice($this->client);

        $taxRegistry = $this->factory->create(WithholdingTaxRuleFile::class, [
            'province' => $this->entityManager->getRepository(Province::class)->find(self::BUENOS_AIRES),
            'fileType' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
            'status' => WithholdingTaxRuleFileStatus::FAILED,
        ]);

        $fileId = $taxRegistry->getId();

        $this->client->request('POST', self::WITHHOLDING_TAX_REGISTRIES_PROVINCE_REGISTER.$fileId.'/delete');

        $this->entityManager->clear();

        $taxRegistryFromDatabase = $this->entityManager->getRepository(WithholdingTaxRuleFile::class)->find($fileId);

        $this->assertNotnull($taxRegistryFromDatabase);
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearDirectoryForTest('./tests/../storage/public/storage/');
    }
}
