<?php

namespace GeoPagos\WithholdingTaxBundle\Command;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxDynamicRuleRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanOldTaxRegistersCommand extends Command
{
    protected static $defaultName = 'geopagos:withholding-taxes:clean-old-tax-registers';

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var WithholdingTaxDynamicRuleRepository */
    private $withholdingTaxDynamicRuleRepository;

    public function __construct(EntityManagerInterface $entityManager, WithholdingTaxDynamicRuleRepository $withholdingTaxDynamicRuleRepository)
    {
        parent::__construct(self::$defaultName);

        $this->entityManager = $entityManager;
        $this->withholdingTaxDynamicRuleRepository = $withholdingTaxDynamicRuleRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription('Clean up old registries entries for non-existing idFiscals.')
            ->addOption(
                'command-id',
                null,
                InputArgument::OPTIONAL,
                'Command Id'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->withholdingTaxDynamicRuleRepository->cleanOldTaxRegisters(Carbon::now());
    }
}
