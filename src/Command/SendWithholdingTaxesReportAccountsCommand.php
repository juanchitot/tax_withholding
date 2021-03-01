<?php

namespace GeoPagos\WithholdingTaxBundle\Command;

use Carbon\Carbon;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\SubsidiaryCertificateGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendWithholdingTaxesReportAccountsCommand extends Command
{
    const NAME = 'geopagos:withholding-taxes:account-reports';

    /** @var SubsidiaryCertificateGenerator */
    private $subsidiaryCertificateGenerator;

    public function __construct(SubsidiaryCertificateGenerator $generateCertificatesBySubsidiary)
    {
        $this->subsidiaryCertificateGenerator = $generateCertificatesBySubsidiary;

        parent::__construct(self::NAME);
    }

    protected function configure()
    {
        $this
            ->setDescription('Generates Withholding Tax reports to be reported to accounts')
            ->addOption(
                'month',
                null,
                InputArgument::OPTIONAL,
                'Month to process. Format YYYYMM.'
            )
            ->addOption(
                'include-old-certificates',
                'o',
                InputOption::VALUE_NONE,
                'Add old period unsent certificates to pdf.'
            )
            ->addOption(
                'command-id',
                null,
                InputArgument::OPTIONAL,
                'Execution id meant to track logs generated by this command.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $month = $this->month($input);
        $sendOldCertificates = $this->getSendOldCertificates($input);

        $this->subsidiaryCertificateGenerator
            ->setMonth($month)
            ->setSendOldCertificates($sendOldCertificates)
            ->process();
    }

    private function month(InputInterface $input)
    {
        return $input->getOption('month') ?? Carbon::now()->startOfMonth()->subDay()->format('Ym');
    }

    private function getSendOldCertificates(InputInterface $input)
    {
        $option = $input->getOption('include-old-certificates');

        return  $option ?? false;
    }
}