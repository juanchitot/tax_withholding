<?php

namespace GeoPagos\WithholdingTaxBundle\Tests;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Entity\TaxRuleProvincesGroup;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Helper\SirtacJurisdictionsHelper;
use GeoPagos\WithholdingTaxBundle\Tests\Integration\Scenes\Scene;
use GeoPagos\WithholdingTaxBundle\Tests\Integration\Scenes\SceneBuilderBaseDirector;
use Symfony\Component\HttpFoundation\ParameterBag;

trait SirtacMocks
{
    use WithholdingMocks;

    public static $A_FISCAL_ID = 11111111111;
    public static $A_SIRTAC_PROVINCE_ID = 66;
    public static $A_IIBB_PROVINCE_ID = 10;

    public static $SIRTAC_INFORMATIVE_RATE = 0;
    public static $SIRTAC_TAX_REGISTRY_RATE = 1;
    public static $SIRTAC_HABITUALITY_RATE = 3.00;
    public static $SIRTAC_PENALTY_RATE = 1.50;
    public static $A_IIBB_PROVINCE_RATE = 2.50;

    /** @var EntityManagerInterface */
    private $entityManager;

    private function buildTrxScene(int $provinceId, int $trxCount, int $trxAmount, $taxCategoryId = null): Scene
    {
        $province = $this->entityManager->getRepository(Province::class)->find($provinceId);
        $director = new SceneBuilderBaseDirector();
        $parameterBag = new ParameterBag([
            'account.idFiscal' => self::$A_FISCAL_ID,
            'subsidiary.address.province' => $province,
            'transactions.count' => $trxCount,
            'transaction.amount' => $trxAmount,
        ]);

        if (null !== $taxCategoryId) {
            $parameterBag->add([
                'subsidiary.taxCategoryId' => $taxCategoryId,
            ]);
        }

        $director->makeMultipleTransactionDepositWithParam($this->sceneBuilder(), $parameterBag);

        return $this->sceneBuilder()->getResult();
    }

    private function addAccountToTaxRegistry(Scene $scene, float $rate, $withPenalty = false, $saleProvinceId = null)
    {
        $provinceGroup = $this->entityManager->getRepository(TaxRuleProvincesGroup::class)->find(1);
        $this->factory->create(WithholdingTaxRuleFile::class, [
            'date' => Carbon::now()->format('m-Y'),
            'province' => null,
            'fileType' => WithholdingTaxRuleFile::SIRTAC_TYPE,
            'status' => WithholdingTaxRuleFileStatus::SUCCESS,
            'imported' => 1,
        ]);

        $statusJurisdictions = '134513451345134513451345';
        if ($withPenalty) {
            $provincePosition = SirtacJurisdictionsHelper::PROVINCE_MAPPING[$saleProvinceId];
            $statusJurisdictions[$provincePosition] = '2';
        }

        $this->factory->create(WithholdingTaxDynamicRule::class, [
            'id_fiscal' => $scene->getAccount()->getIdFiscal(),
            'month_year' => Carbon::now()->format('m-Y'),
            'rate' => $rate,
            'province' => null,
            'type' => WithholdingTaxTypeEnum::TAX_SIRTAC_TYPE,
            'provinces_group' => $provinceGroup,
            'status_jurisdictions' => $statusJurisdictions,
            'crc' => 12,
        ]);
    }
}
