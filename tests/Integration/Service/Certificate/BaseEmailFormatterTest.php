<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Service\Certificate;

use Doctrine\ORM\EntityManagerInterface;
use DOMXPath;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\User;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\Certificate;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTax;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\CreateRequest;
use GeoPagos\WithholdingTaxBundle\Model\Certificate\Package;
use GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Builder;
use GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Formatter\BaseEmailPdfFormatter;
use GeoPagos\WithholdingTaxBundle\Services\WithholdStage\GananciasWithholdStage;
use League\Flysystem\FilesystemInterface;

class BaseEmailFormatterTest extends TestCase
{
    use FactoriesTrait;

    const INSCRIPTO_LOCAL = 1;

    /** @var EntityManagerInterface */
    private $em;
    /**
     * @var FilesystemInterface
     */
    private $fileSystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = self::$container->get(EntityManagerInterface::class);
        $this->fileSystem = self::$container->get('local_filesystem');
    }

    /** @test * */
    public function base_email_formatter_with_ganancias_5_rows()
    {
        $subsidiary = $this->factory->create(Subsidiary::class, [
            'taxCategory' => $this->em->getRepository(TaxCategory::class)->find(self::INSCRIPTO_LOCAL),
            'account' => $this->factory->create(Account::class, [
                'owner' => $this->factory->create(User::class),
            ]),
        ]);
        /* @var $package Package */
        $certificate = $this->factory->instance(Certificate::class, ['subsidiary' => $subsidiary]);
        $taxType = GananciasWithholdStage::getTaxType();
        $package = $this->factory->instance(Package::class,
            [
                'taxSettings' => $this->factory->instance(ProvinceWithholdingTaxSetting::class),
                'certificateEntity' => $certificate,
                'taxType' => $taxType,
            ]
        )->setData($this->factory->seed(6, WithholdingTax::class, [
            'subsidiary' => $subsidiary,
            'type' => $taxType,
        ]));

        $createRequest = $this->factory->instance(CreateRequest::class, [
            'fiscalId' => $package->getFiscalId(),
            'subsidiary' => $package->getSubsidiary(),
            'period' => $package->getPeriod(),
        ]);

        $builder = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $builder->method('getCreateRequest')
            ->willReturn($createRequest);

        $baseFormatter = self::$container->get(BaseEmailPdfFormatter::class);
        $result = $baseFormatter->format($builder, $package);

        //file_get_contents($result)
        $document = new \DOMDocument();
        $document->loadHTML($this->fileSystem->read('./public/'.$result));
        $xpath = new DOMXPath($document);

        $receiptTag = $xpath->query("//*[contains(@class, 'receipt-number')]");
        $this->assertCount(1, $receiptTag);
        $firstWithholdingTax = $package->getData()[0]->getCertificateNumber();
        $this->assertStringContainsString($package->getData()[0]->getCertificateNumber(), $receiptTag[0]->textContent);

        $rows = $xpath->query("//tr[contains(@class, 'withhold-row')]");
        /* @var $row \DOMElement */

        $this->assertEquals(count($rows), count($package->getData()));

        $rowIndex = 0;
        foreach ($rows as $row) {
            /* @var $childs \DOMNodeList */
            $childs = $row->getElementsByTagName('td');
            /* @var $sourceRow WithholdingTax */
            $sourceRow = $package->getData()[$rowIndex];

            $nodeFecha = $childs[0]->textContent;
            $this->assertStringContainsString($sourceRow->getDate()->format('d/m/Y'), $nodeFecha);

            $nodeNroLiquidacion = $childs[1]->textContent;

            $nodeAlicuota = $childs[2]->textContent;
            $this->assertStringContainsString($sourceRow->getRate(), $nodeAlicuota);

            $nodeBaseImponible = $childs[3]->textContent;
            $this->assertStringContainsString(number_format($sourceRow->getTaxableIncome(), 2, ',', '.'),
                $nodeBaseImponible);

            $nodeAmount = $childs[4]->textContent;
            $this->assertStringContainsString(number_format($sourceRow->getAmount(), 2, ',', '.'), $nodeAmount);

            ++$rowIndex;
        }
    }
}
