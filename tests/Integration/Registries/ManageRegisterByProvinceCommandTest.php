<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Registries;

use Carbon\Carbon;
use Cmixin\BusinessDay;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Repository\ProvinceRepository;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Command\ManageWithholdingTaxRegistersCommand;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use League\Flysystem\FilesystemInterface;
use ZipArchive;

class ManageRegisterByProvinceCommandTest extends TestCase
{
    use FactoriesTrait;

    const CABA = 2;
    const BUENOS_AIRES = 6;
    const CORDOBA = 14;
    const SANTA_CRUZ = 78;

    const PBA_ACRONYM = 'pba';
    const CABA_ACRONYM = 'caba';
    const CORDOBA_ACRONYM = 'cba';
    const REGISTRIES_DIR = 'files';
    const SANTA_CRUZ_ACRONYM = 'scruz';

    /** @var ProvinceRepository */
    private $provinceRepository;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var EntityManagerInterface */
    private $em;

    public $configurationManager;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now());
        BusinessDay::enable('Carbon\Carbon');

        $this->em = self::$container->get(EntityManagerInterface::class);
        $this->filesystem = self::$container->get('local_filesystem');
    }

    /** @test * */
    public function it_can_process_santa_cruz_registry()
    {
        Carbon::setTestNow('2020-11');
        $santa_cruz = $this->em->getRepository(Province::class)->find(self::SANTA_CRUZ);

        /** @var WithholdingTaxRuleFile $entry */
        $entry = $this->factory->create(WithholdingTaxRuleFile::class, [
            'province' => $santa_cruz,
            'date' => Carbon::now()->endOfMonth()->addDay()->format('m-Y'),
            'file_type' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
        ]);

        $this->prepareRegistryFileAsUploadedFromBackoffice($entry);

        $this->runCommandWithParameters(ManageWithholdingTaxRegistersCommand::NAME);
        $this->em->clear();

        $registry = $this->em->getRepository(WithholdingTaxDynamicRule::class)->findAll();
        $wtRegisterProvince = $this->em->getRepository(WithholdingTaxRuleFile::class)->findAll()[0];

        $this->assertCount(12, $registry);
        $this->assertEquals(12, $wtRegisterProvince->getImported());
    }

    /** @test * */
    public function it_can_process_cordoba_registry()
    {
        Carbon::setTestNow('2020-10');
        $cordoba = $this->em->getRepository(Province::class)->find(self::CORDOBA);

        /** @var WithholdingTaxRuleFile $entry */
        $entry = $this->factory->create(WithholdingTaxRuleFile::class, [
            'province' => $cordoba,
            'date' => Carbon::now()->endOfMonth()->addDay()->format('m-Y'),
            'file_type' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
        ]);

        $this->prepareRegistryFileAsUploadedFromBackoffice($entry);

        $this->runCommandWithParameters(ManageWithholdingTaxRegistersCommand::NAME);
        $this->em->clear();

        $registry = $this->em->getRepository(WithholdingTaxDynamicRule::class)->findAll();
        $wtRegisterProvince = $this->em->getRepository(WithholdingTaxRuleFile::class)->findAll()[0];

        $this->assertCount(12, $registry);
        $this->assertEquals(12, $wtRegisterProvince->getImported());
    }

    /** @test */
    public function it_can_process_pba_registry(): void
    {
        $pba = $this->em->getRepository(Province::class)->find(self::BUENOS_AIRES);

        /** @var WithholdingTaxRuleFile $entry */
        $entry = $this->factory->create(WithholdingTaxRuleFile::class, [
            'province' => $pba,
            'date' => Carbon::now()->endOfMonth()->addDay()->format('m-Y'),
            'file_type' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
        ]);

        $this->prepareRegistryFileAsUploadedFromBackoffice($entry);

        $this->runCommandWithParameters(ManageWithholdingTaxRegistersCommand::NAME);
        $this->em->clear();

        $registry = $this->em->getRepository(WithholdingTaxDynamicRule::class)->findAll();
        $wtRegisterProvince = $this->em->getRepository(WithholdingTaxRuleFile::class)->findAll()[0];

        $this->assertCount(10, $registry);
        $this->assertEquals(10, $wtRegisterProvince->getImported());
    }

    /** @test */
    public function it_can_process_caba_registry(): void
    {
        $caba = $this->em->getRepository(Province::class)->find(self::CABA);

        /** @var WithholdingTaxRuleFile $entry */
        $entry = $this->factory->create(WithholdingTaxRuleFile::class, [
            'province' => $caba,
            'date' => Carbon::now()->endOfMonth()->addDay()->format('m-Y'),
            'file_type' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
        ]);

        $this->prepareRegistryFileAsUploadedFromBackoffice($entry);

        $this->runCommandWithParameters(ManageWithholdingTaxRegistersCommand::NAME);
        $this->em->clear();

        $registry = $this->em->getRepository(WithholdingTaxDynamicRule::class)->findAll();
        $wtRegisterProvince = $this->em->getRepository(WithholdingTaxRuleFile::class)->findAll()[0];

        $this->assertCount(10, $registry);
        $this->assertEquals(10, $wtRegisterProvince->getImported());
    }

    /** @test */
    public function it_can_process_pba_registry_compressed_as_ZIP(): void
    {
        $pba = $this->em->getRepository(Province::class)->find(self::BUENOS_AIRES);

        /** @var WithholdingTaxRuleFile $entry */
        $entry = $this->factory->create(WithholdingTaxRuleFile::class, [
            'province' => $pba,
            'dbFile' => 'caba_test_registry.zip',
            'date' => Carbon::now()->endOfMonth()->addDay()->format('m-Y'),
            'file_type' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
        ]);

        $this->prepareRegistryFileAsUploadedFromBackoffice($entry);

        $this->runCommandWithParameters(ManageWithholdingTaxRegistersCommand::NAME);
        $this->em->clear();

        $registry = $this->em->getRepository(WithholdingTaxDynamicRule::class)->findAll();
        $wtRegisterProvince = $this->em->getRepository(WithholdingTaxRuleFile::class)->findAll()[0];

        $this->assertCount(10, $registry);
        $this->assertEquals(10, $wtRegisterProvince->getImported());
    }

    /** @test */
    public function it_can_process_caba_registry_compressed_as_RAR(): void
    {
        $caba = $this->em->getRepository(Province::class)->find(self::CABA);

        /** @var WithholdingTaxRuleFile $entry */
        $entry = $this->factory->create(WithholdingTaxRuleFile::class, [
            'province' => $caba,
            'dbFile' => 'caba_test_registry.rar',
            'date' => Carbon::now()->endOfMonth()->addDay()->format('m-Y'),
            'file_type' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
        ]);

        $this->prepareRegistryFileAsUploadedFromBackoffice($entry);

        $this->runCommandWithParameters(ManageWithholdingTaxRegistersCommand::NAME);

        $registry = $this->em->getRepository(WithholdingTaxDynamicRule::class)->findAll();

        $this->assertCount(10, $registry);
    }

    /** @test */
    public function it_can_process_a_file_with_three_errors_max(): void
    {
        $caba = $this->em->getRepository(Province::class)->find(self::CABA);

        /** @var WithholdingTaxRuleFile $entry */
        $entry = $this->factory->create(WithholdingTaxRuleFile::class, [
            'province' => $caba,
            'date' => Carbon::now()->endOfMonth()->addDay()->format('m-Y'),
            'file_type' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
        ]);

        $this->prepareRegistryFileWithFailsAsUploadedFromBackoffice($entry, 3);

        $this->runCommandWithParameters(ManageWithholdingTaxRegistersCommand::NAME);
        $this->em->clear();

        $registry = $this->em->getRepository(WithholdingTaxDynamicRule::class)->findAll();
        $wtRegisterProvince = $this->em->getRepository(WithholdingTaxRuleFile::class)->findAll()[0];

        $this->assertCount(7, $registry);
        $this->assertEquals(7, $wtRegisterProvince->getImported());
    }

    /** @test */
    public function it_fail_if_file_has_more_than_three_format_errors(): void
    {
        $caba = $this->em->getRepository(Province::class)->find(self::CABA);

        /** @var WithholdingTaxRuleFile $entry */
        $entry = $this->factory->create(WithholdingTaxRuleFile::class, [
            'province' => $caba,
            'date' => Carbon::now()->endOfMonth()->addDay()->format('m-Y'),
            'file_type' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
        ]);

        $this->prepareRegistryFileWithFailsAsUploadedFromBackoffice($entry, 4);

        $this->runCommandWithParameters(ManageWithholdingTaxRegistersCommand::NAME);
        $this->em->clear();

        $registry = $this->em->getRepository(WithholdingTaxDynamicRule::class)->findAll();
        $wtRegisterProvince = $this->em->getRepository(WithholdingTaxRuleFile::class)->findAll()[0];

        $this->assertCount(0, $registry);
        $this->assertEquals(0, $wtRegisterProvince->getImported());
    }

    /** @test */
    public function it_can_process_micro_enterprice_registry(): void
    {
        /** @var WithholdingTaxRuleFile $entry */
        $entry = $this->factory->create(WithholdingTaxRuleFile::class, [
            'date' => Carbon::now()->endOfMonth()->addDay()->format('m-Y'),
            'fileType' => WithholdingTaxRuleFile::MICRO_ENTERPRISE,
        ]);

        $this->prepareRegistryFileAsUploadedFromBackoffice($entry);

        $this->runCommandWithParameters(ManageWithholdingTaxRegistersCommand::NAME);
        $this->em->clear();

        $registry = $this->em->getRepository(WithholdingTaxDynamicRule::class)->findAll();
        $wtRegisterProvince = $this->em->getRepository(WithholdingTaxRuleFile::class)->findAll()[0];

        $this->assertCount(10, $registry);
        $this->assertEquals(10, $wtRegisterProvince->getImported());
    }

    private function prepareGrossIncomeTestRegistry(Province $province, $compresion = null)
    {
        if (!in_array($province->getAcronym(), [
            self::PBA_ACRONYM,
            self::CABA_ACRONYM,
            self::CORDOBA_ACRONYM,
            self::SANTA_CRUZ_ACRONYM,
        ])) {
            throw new \Exception('This province doesn\'t have registry.');
        }

        $extension = $compresion ?? 'txt';

        $baseFilename = $province->getAcronym().'_test_registry.txt';
        $baseFile = str_replace(
            '||MONTHYEAR||',
            Carbon::now()->endOfMonth()->addDay()->format('mY'),
            file_get_contents(__DIR__.DIRECTORY_SEPARATOR.self::REGISTRIES_DIR.DIRECTORY_SEPARATOR.$baseFilename)
        );

        switch ($extension) {
            case 'zip':
                // Create a tmp file and return the content
                $tmpZipFile = tempnam('/tmp', 'geopagos');
                $zip = new ZipArchive();
                $zip->open($tmpZipFile, ZIPARCHIVE::CREATE);
                $zip->addFromString($baseFilename, $baseFile);
                $zip->close();
                $file = file_get_contents($tmpZipFile);

                break;
            case 'rar':
                // NOT YET IMPLEMENTED
                $file = $baseFile;

                break;
            default:
                $file = $baseFile;
        }

        return $file;
    }

    private function prepareMicroEnterpriseTestRegistry($compresion = null)
    {
        $extension = $compresion ?? 'txt';

        $baseFilename = 'micro_enterprise_test_registry.txt';
        $baseFile = str_replace(
            '||MONTHYEAR||',
            Carbon::now()->startOfMonth()->format('Ym'),
            file_get_contents(__DIR__.DIRECTORY_SEPARATOR.self::REGISTRIES_DIR.DIRECTORY_SEPARATOR.$baseFilename)
        );

        switch ($extension) {
            case 'zip':
                // Create a tmp file and return the content
                $tmpZipFile = tempnam('/tmp', 'geopagos');
                $zip = new ZipArchive();
                $zip->open($tmpZipFile, ZIPARCHIVE::CREATE);
                $zip->addFromString($baseFilename, $baseFile);
                $zip->close();
                $file = file_get_contents($tmpZipFile);

                break;
            case 'rar':
                // NOT YET IMPLEMENTED
                $file = $baseFile;

                break;
            default:
                $file = $baseFile;
        }

        return $file;
    }

    private function prepareGrossIncomeFailedTestRegistry(Province $province, $compresion = null, int $failedLines = 1)
    {
        $file = $this->prepareGrossIncomeTestRegistry($province, $compresion);

        $lines = preg_split("/\r\n|\n|\r/", $file);
        $newFile = [];
        foreach ($lines as $pos => $line) {
            if ($pos < $failedLines) {
                $newFile[] = 'FAILFORMATLINE';
            } else {
                $newFile[] = $line;
            }
        }

        return join("\n", $newFile);
    }

    private function prepareRegistryFileAsUploadedFromBackoffice(WithholdingTaxRuleFile $entry)
    {
        switch ($entry->getFileType()) {
            case WithholdingTaxRuleFile::GROSS_INCOME_TYPE:
                $this->filesystem->put(
                    'public/'.$entry->getDbFile(),
                    $this->prepareGrossIncomeTestRegistry(
                        $entry->getProvince(),
                        pathinfo($entry->getDbFile(), PATHINFO_EXTENSION)
                    )
                );

                break;
            case WithholdingTaxRuleFile::MICRO_ENTERPRISE:
                $this->filesystem->put(
                    'public/'.$entry->getDbFile(),
                    $this->prepareMicroEnterpriseTestRegistry(
                        pathinfo($entry->getDbFile(), PATHINFO_EXTENSION)
                    )
                );

                break;
        }
    }

    private function prepareRegistryFileWithFailsAsUploadedFromBackoffice(
        WithholdingTaxRuleFile $entry,
        int $failedLines = 1
    ) {
        $this->filesystem->put(
            'public/'.$entry->getDbFile(),
            $this->prepareGrossIncomeFailedTestRegistry(
                $entry->getProvince(),
                pathinfo($entry->getDbFile(), PATHINFO_EXTENSION),
                $failedLines
            )
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearDirectoryForTest('./tests/../storage/public/');
    }
}
