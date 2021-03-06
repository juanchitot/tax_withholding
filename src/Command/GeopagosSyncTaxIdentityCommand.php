<?php

namespace GeoPagos\WithholdingTaxBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Repository\AccountRepository;
use GeoPagos\WithholdingTaxBundle\Adapter\TaxInformationAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GeopagosSyncTaxIdentityCommand extends Command
{
    protected static $defaultName = 'geopagos:sync-tax-identity';

    protected static $batch = 500;
    /**
     * @var TaxInformationAdapter
     */
    private $adapter;
    /**
     * @var AccountRepository
     */
    private $repository;
    /**
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(TaxInformationAdapter $adapter, AccountRepository $repository, EntityManagerInterface $manager)
    {
        parent::__construct(self::$defaultName);
        $this->adapter = $adapter;
        $this->repository = $repository;
        $this->manager = $manager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Sync tax condition on accounts')
            ->addOption(
                'command-id',
                null,
                InputArgument::OPTIONAL,
                'Execution id meant to track logs generated by this command.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->section('Tax Condition Sync');

        $iterator = $this->repository->getIterator();

        if ($iterator instanceof IterableResult) {
            $i = 0;
            ProgressBar::setFormatDefinition(
                'custom',
                '%current% [%bar%] %elapsed%/%memory% -- %message% (%filename%)'
            );
            $progressBar = new ProgressBar($output);
            $progressBar->setFormat('custom');
            $progressBar->setMessage('Processing tax condition on accounts...');
            $progressBar->start();

            foreach ($iterator as $item) {
                /** @var Account $account */
                $account = $item[0];

                $this->adapter->taxInformation($account->getIdFiscal());

                if (0 === $i % self::$batch) {
                    $this->manager->flush();
                    $this->manager->clear();
                }

                ++$i;
                $progressBar->advance();
            }

            $this->manager->flush();
            $progressBar->setMessage('Finished.');
            $progressBar->finish();
            $io->newLine(2);
        }

        $io->success('Command finished successfully');

        return 0;
    }
}
