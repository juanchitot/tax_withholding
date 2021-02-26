<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\BackOfficeUser;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\Tests\TestCase;
use GeoPagos\Tests\Traits\BackOfficeAuthenticationTrait;
use GeoPagos\Tests\Traits\FactoriesTrait;
use GeoPagos\WithholdingTaxBundle\Entity\Certificate;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Tests\WithholdingMocks;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WithholdingTaxCertificatesControllerTest extends TestCase
{
    use BackOfficeAuthenticationTrait;

    use FactoriesTrait;

    use WithholdingMocks;

    /** @var EntityManagerInterface */
    private $em;

    /** @var FilesystemInterface */
    private $filesystem;

    /** @var UrlGeneratorInterface */
    private $router;

    protected function setUp(): void
    {
        parent::setUp();
        $this->em = self::$container->get(EntityManagerInterface::class);
        $this->router = self::$container->get(UrlGeneratorInterface::class);
        $project_dir = self::$container->getParameter('kernel.project_dir');
        $this->filesystem = new Filesystem(new Local($project_dir.'/storage/public/'));
        self::$container->set(FilesystemInterface::class, $this->filesystem);
    }

    /** @test */
    public function it_should_show_item_block_when_feature_is_enabled()
    {
        $this->setConfigurationManager(true);

        $this->loginIntoBackoffice($this->client);

        $account = $this->factory->create(Account::class);
        $this->assertNotEmpty($account);

        $crawler = $this->client->request('GET', "/merchants/{$account->getId()}");
        $this->assertResponseIsSuccessful();

        $crawler = $crawler->filter('a[id="modules.withholding_tax_certificates"]');
        $this->assertNotNull($crawler->getNode(0));
    }

    /** @test */
    public function it_should_show_item_block_when_feature_is_disabled_and_user_is_ultradmin()
    {
        $this->setConfigurationManager(false);

        $this->loginIntoBackoffice($this->client);

        $account = $this->factory->create(Account::class);
        $this->assertNotEmpty($account);

        $crawler = $this->client->request('GET', "/merchants/{$account->getId()}");
        $this->assertResponseIsSuccessful();

        $crawler = $crawler->filter('a[id="modules.withholding_tax_certificates"]');
        $this->assertNotNull($crawler->getNode(0));
    }

    /** @test */
    public function it_should_restrict_access_when_feature_is_disabled_and_user_is_not_ultradmin()
    {
        $this->setConfigurationManager(false);

        $user = $this->em->getRepository(BackOfficeUser::class)->findBy([
            'email' => 'admin@geopagos.com',
        ])[0];
        $user->setUltraAdmin(null);
        $this->em->persist($user);
        $this->em->flush();

        $this->loginIntoBackoffice($this->client);

        $account = $this->factory->create(Account::class);
        $this->assertNotEmpty($account);

        $this->client->request('GET', "/withholding-tax-certificates/{$account->getId()}");
        $this->assertResponseStatusCodeSame(403);
    }

    /** @test */
    public function it_should_show_certificates_list()
    {
        $this->setConfigurationManager(true);

        $this->loginIntoBackoffice($this->client);

        $account = $this->buildCertificates();

        $currentDate = Carbon::now()->startOfMonth()->startOfDay()->format('Y-m-d');
        $this->client->request(
            'GET',
            "/withholding-tax-certificates/{$account->getId()}/list",
            [
                'withholding_tax_certificates_filter' => [
                    'periodFrom' => $currentDate,
                    'periodTo' => $currentDate,
                    'taxType' => '',
                ],
            ],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertCount(3, $responseData['data']);
        $this->assertSame(3, $responseData['recordsFiltered']);
        $this->assertSame(3, $responseData['recordsTotal']);
    }

    /** @test */
    public function it_can_filter_certificates_list()
    {
        $this->setConfigurationManager(true);

        $this->loginIntoBackoffice($this->client);

        $account = $this->buildCertificates();

        $currentDate = Carbon::now()->startOfMonth()->startOfDay()->format('Y-m-d');
        $this->client->request(
            'GET',
            "/withholding-tax-certificates/{$account->getId()}/list",
            [
                'withholding_tax_certificates_filter' => [
                    'periodFrom' => $currentDate,
                    'periodTo' => $currentDate,
                    'taxType' => WithholdingTaxTypeEnum::TAX,
                ],
            ],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);

        $this->assertCount(1, $responseData['data']);
        $this->assertSame(1, $responseData['recordsFiltered']);
        $this->assertSame(1, $responseData['recordsTotal']);
    }

    /** @test */
    public function it_can_load_index_page()
    {
        $this->setConfigurationManager(true);

        $this->loginIntoBackoffice($this->client);

        $account = $this->factory->create(Account::class);
        $this->assertNotEmpty($account);

        $this->client->request('GET', "/withholding-tax-certificates/{$account->getId()}");
        $this->assertResponseIsSuccessful();
    }

    /** @test */
    public function a_certificate_without_a_valid_file_cant_be_downloaded()
    {
        $this->setConfigurationManager(true);

        $this->loginIntoBackoffice($this->client);

        $this->buildCertificates();

        $certificate = $this->em->getRepository(Certificate::class)->findAll()[0];
        $this->client->request('GET', "/withholding-tax-certificates/{$certificate->getId()}/download");
        $this->assertResponseStatusCodeSame(500);
    }

    /** @test */
    public function a_certificate_without_a_valid_file_cant_be_viewed()
    {
        $this->setConfigurationManager(true);

        $this->loginIntoBackoffice($this->client);

        $this->buildCertificates();

        $certificate = $this->em->getRepository(Certificate::class)->findAll()[0];
        $this->client->request('GET', "/withholding-tax-certificates/{$certificate->getId()}/view");
        $this->assertResponseStatusCodeSame(500);
    }

    /** @test */
    public function all_filtered_certificates_can_be_downloaded_as_zip()
    {
        $this->setConfigurationManager(true);

        $this->loginIntoBackoffice($this->client);

        $this->buildCertificates();
        // Create file for zip file to take
        $this->createMockTaxFile();

        /** @var Certificate[] $certificate */
        $certificates = $this->em->getRepository(Certificate::class)->findAll();
        $this->client->request('GET', $this->router->generate('common_back_withholding_tax_certificates_download_zip', [
            'merchant_id' => $certificates[0]->getSubsidiary()->getAccount()->getId(),
            'periodFrom' => Carbon::now()->startOfYear()->format('Y-m-d'),
            'periodTo' => Carbon::now()->endOfYear()->format('Y-m-d'),
        ]));
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHasHeader('Content-Type', 'application/zip');
    }

    private function setConfigurationManager($isShowCertificatesInBackofficeEnabled)
    {
        $this->getMockedConfigurationManager(
            false,
            false,
            false,
            false,
            $isShowCertificatesInBackofficeEnabled
        );
    }

    private function buildCertificates(): Account
    {
        /** @var Account $account */
        $account = $this->factory->create(Account::class);
        $subsidiary = $this->factory->create(Subsidiary::class, [
            'account' => $account,
        ]);

        $this->factory->create(Certificate::class, [
            'subsidiary' => $subsidiary,
            'type' => WithholdingTaxTypeEnum::TAX,
            'fileName' => '/certificate/'.WithholdingTaxTypeEnum::TAX.'.pdf',
        ]);

        $this->factory->create(Certificate::class, [
            'subsidiary' => $subsidiary,
            'type' => WithholdingTaxTypeEnum::INCOME_TAX,
            'fileName' => '/certificate/'.WithholdingTaxTypeEnum::INCOME_TAX.'.pdf',
        ]);

        $this->factory->create(Certificate::class, [
            'subsidiary' => $subsidiary,
            'type' => WithholdingTaxTypeEnum::VAT,
            'fileName' => '/certificate/'.WithholdingTaxTypeEnum::VAT.'.pdf',
        ]);

        return $account;
    }

    private function createMockTaxFile()
    {
        $this->filesystem->put('/certificate/TAX.pdf', 'Any content');
        $this->filesystem->put('/certificate/INCOME_TAX.pdf', 'Any content');
        $this->filesystem->put('/certificate/VAT.pdf', 'Any content');
    }
}
