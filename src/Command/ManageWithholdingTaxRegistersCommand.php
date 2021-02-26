<?php

namespace GeoPagos\WithholdingTaxBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use GeoPagos\WithholdingTaxBundle\Model\RuleFileParserFactory;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\ManageRegisterByProvinceService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ManageWithholdingTaxRegistersCommand extends ContainerAwareCommand
{
    const NAME = 'geopagos:withholding-taxes:manage-registers';

    /** @var ManageRegisterByProvinceService */
    private $registerByProvinceService;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, ManageRegisterByProvinceService $registerByProvinceService)
    {
        parent::__construct(self::NAME);

        $this->entityManager = $entityManager;

        $this->registerByProvinceService = $registerByProvinceService;
    }

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Process pending registries.')
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Force running the cron.'
            )
            ->addOption(
                'command-id',
                null,
                InputArgument::OPTIONAL,
                'Command Id'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pendingRegisters = $this->entityManager->getRepository(WithholdingTaxRuleFile::class)->findBy([
            'status' => WithholdingTaxRuleFileStatus::PENDING,
        ]);

        RuleFileParserFactory::setEntityManager($this->entityManager);

        /** @var WithholdingTaxRuleFile $pendingRegister */
        foreach ($pendingRegisters as $pendingRegister) {
            $this->registerByProvinceService
                ->setRegisterProvince($pendingRegister)
                ->setParser(RuleFileParserFactory::getParser($pendingRegister))
                ->setForce($input->getOption('force'));

            $this->registerByProvinceService->process();
        }
    }
}
