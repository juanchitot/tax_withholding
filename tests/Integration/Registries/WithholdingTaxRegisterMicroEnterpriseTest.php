<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Registries;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\BackOfficeAuthenticationTrait;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class WithholdingTaxRegisterMicroEnterpriseTest extends TestCase
{
    private const MICRO_ENTERPRISE_FILE = 'micro_enterprise_test_registry.txt';

    private const REGISTRY_DIR = '/files';
    const WITHHOLDING_TAX_REGISTRIES_MICRO_ENTERPRISE = '/withholding-tax-registries/micro-enterprise/';

    /** @var FilesystemInterface */
    private $filesystem;
    /** @var EntityManagerInterface */
    private $entityManager;

    use BackOfficeAuthenticationTrait;

    use FactoriesTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = self::$container->get('doctrine')->getManager();
        $this->filesystem = self::$container->get('local_filesystem');
    }

    /** @test * */
    public function shows_general_register_section()
    {
        $this->loginIntoBackoffice($this->client);

        $this->client->request('GET', self::WITHHOLDING_TAX_REGISTRIES_MICRO_ENTERPRISE);

        $this->assertResponseIsSuccessful();
    }

    /** @test * */
    public function shows_upload_form()
    {
        $this->loginIntoBackoffice($this->client);

        $this->client->request('GET', self::WITHHOLDING_TAX_REGISTRIES_MICRO_ENTERPRISE.'new');

        $this->assertResponseIsSuccessful();
    }

    /** @test * */
    public function it_creates_a_new_micro_enterprise_register()
    {
        $this->loginIntoBackoffice($this->client);

        $uploadedFile = new UploadedFile(
            __DIR__.self::REGISTRY_DIR.DIRECTORY_SEPARATOR.self::MICRO_ENTERPRISE_FILE,
            self::MICRO_ENTERPRISE_FILE, null, false, true
        );

        $this->client->request('POST', self::WITHHOLDING_TAX_REGISTRIES_MICRO_ENTERPRISE.'new', [
            'withholding_tax_register_micro_enterprise' => [
                'save' => '',
                'dbFile' => $uploadedFile,
            ], ], [
                $uploadedFile,
            ]
        );

        $microEnterpriseFileRules = $this->entityManager
            ->getRepository(WithholdingTaxRuleFile::class)
            ->findBy([
                    'fileType' => WithholdingTaxRuleFile::MICRO_ENTERPRISE,
                ]
            );

        $this->assertResponseRedirects();
        $this->assertCount(1, $microEnterpriseFileRules);
    }

    /** @test * */
    public function it_can_delete_pending_registries()
    {
        $this->loginIntoBackoffice($this->client);

        $taxRegistry = $this->factory->create(WithholdingTaxRuleFile::class, [
            'fileType' => WithholdingTaxRuleFile::MICRO_ENTERPRISE,
        ]);

        $fileId = $taxRegistry->getId();

        $this->client->request('POST', self::WITHHOLDING_TAX_REGISTRIES_MICRO_ENTERPRISE.$fileId.'/delete');

        $this->entityManager->clear();

        $taxRegistryFromDatabase = $this->entityManager->getRepository(WithholdingTaxRuleFile::class)->find($fileId);

        $this->assertNull($taxRegistryFromDatabase);
    }

    /** @test * */
    public function it_cant_delete_failed_registries()
    {
        $this->loginIntoBackoffice($this->client);

        $taxRegistry = $this->factory->create(WithholdingTaxRuleFile::class, [
            'fileType' => WithholdingTaxRuleFile::MICRO_ENTERPRISE,
            'status' => WithholdingTaxRuleFileStatus::FAILED,
        ]);

        $fileId = $taxRegistry->getId();

        $this->client->request('POST', self::WITHHOLDING_TAX_REGISTRIES_MICRO_ENTERPRISE.$fileId.'/delete');

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
