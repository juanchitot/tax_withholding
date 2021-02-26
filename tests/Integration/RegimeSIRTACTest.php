<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\ApiAuthenticationTrait;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\TaxRuleProvincesGroup;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Model\RuleFileParserFactory;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxDynamicRuleProvinceRateRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxDynamicRuleRepository;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\ManageRegisterByProvinceService;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class RegimeSIRTACTest extends TestCase
{
    use FactoriesTrait;
    use ApiAuthenticationTrait;

    private const CORDOBA = 14;

    private const FIRST_ID_FISCAL = '20000077047';
    private const FIRST_STATUS_JUR = '522525521252255555252255';

    /**
     * @var EntityManagerInterface
     */
    private $em;
    private $SIRTACcontentReport = <<<EOT
20000077047;Contribuyente Local;916;202008;14;A;5225255212522555552522550
20000098508;Contribuyente Local;916;202008;93;A;5225255212522555552522550
20000156982;Contribuyente Local;917;202008;62;W;5225255122522555552522550
20000962997;SUCESION DE FINCATI MARIA DOLORES;0;202008;71;A;5225255222522555552522440
20002040590;SUC LUIS T BALBIANI;0;202008;55;A;5225255222522555552522440
20002051142;LARGUIA ALFREDO EDUARDO;0;202008;66;A;5225255222522555552522440
20001151712;COMAS JORGE MARIANO;0;202008;62;A;5224255222522555552522540
20017466667;BARREIRO JOSE HUMBERTO;0;202008;76;H;5225255222522555552512540
EOT;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = self::$container->get(EntityManagerInterface::class);
    }

    /** @test * */
    public function a_SIRTAC_registry_can_be_parser_is_the_rule_has_registry()
    {
        Carbon::setTestNow('2020-07');
        $kernel = self::$container->get(KernelInterface::class);

        $withholdingTaxDynamicRuleProvinceRateRepository = $this->createStub(WithholdingTaxDynamicRuleProvinceRateRepository::class);
        $withholdingTaxDynamicRuleRepository = $this->createStub(WithholdingTaxDynamicRuleRepository::class);
        $logger = $this->createStub(LoggerInterface::class);

        $fileDate = Carbon::now()->endOfMonth()->addDay()->format('m-Y');

        /** @var WithholdingTaxRuleFile $pendingRegisterUpload */
        $group = $this->em->getRepository(TaxRuleProvincesGroup::class)->findOneBy([
            'name' => 'SIRTAC',
        ]);

        $pendingRegisterUpload = $this->factory->create(WithholdingTaxRuleFile::class, [
            'date' => $fileDate,
            'file_type' => WithholdingTaxRuleFile::SIRTAC_TYPE,
            'provinces_group' => $group,
        ]);

        $filesystem = new Filesystem(new MemoryAdapter());

        // osea q esta kk es  .TXT , que adentro es un CSV
        $filesystem->write($pendingRegisterUpload->getDbFile(), $this->SIRTACcontentReport);

        $managerRegisterProvince = new ManageRegisterByProvinceService(
            $withholdingTaxDynamicRuleProvinceRateRepository,
            $withholdingTaxDynamicRuleRepository,
            $this->em,
            $filesystem,
            $logger,
            $kernel
        );

        RuleFileParserFactory::setEntityManager($this->em);
        $parser = RuleFileParserFactory::getParser($pendingRegisterUpload);

        $managerRegisterProvince
            ->setRegisterProvince($pendingRegisterUpload)
            ->setParser($parser)
            ->setForce(false);

        $resultOfParsedFile = $managerRegisterProvince->process();

        $this->assertTrue($resultOfParsedFile);

        $dynamicRules = $this->em->getRepository(WithholdingTaxDynamicRule::class)->findBy(
            [],
            ['id' => 'ASC']
        );

        $this->assertNotEmpty($dynamicRules);

        $this->assertSame($dynamicRules[0]->getIdFiscal(), self::FIRST_ID_FISCAL);
        $this->assertSame($dynamicRules[0]->getStatusJurisdictions(), self::FIRST_STATUS_JUR);

        /** @var WithholdingTaxDynamicRule $rule */
        foreach ($dynamicRules as $rule) {
            $this->assertSame($group, $rule->getProvincesGroup());
        }
    }
}
