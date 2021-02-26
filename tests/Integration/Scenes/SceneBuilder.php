<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Scenes;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use GeoPagos\ApiBundle\Entity\Account;
use GeoPagos\ApiBundle\Entity\Address;
use GeoPagos\ApiBundle\Entity\PaymentMethod;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\TaxCondition;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\ApiBundle\Entity\TransactionDetail;
use GeoPagos\ApiBundle\Entity\User;
use GeoPagos\DepositBundle\Entity\Deposit;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use League\FactoryMuffin\FactoryMuffin;
use Money\Currency;
use Symfony\Component\HttpFoundation\ParameterBag;

class SceneBuilder
{
    const DEFAULT_NUMBER_OF_TRANSACTIONS = 1;
    const DEFAULT_TRANSACTIONS_AMOUNT = 5000;
    const DEFAULT_TRANSACTIONS_COMMISSION = 0.1;
    const DEFAULT_TRANSACTIONS_COMMISSION_TAX = 0.5;
    const DEFAULT_TRANSACTIONS_PAYMENT_METHOD_ID = 1;
    const DEFAULT_SUBSIDIARY_TAX_CONDITION_ID = 5;
    const DEFAULT_SUBSIDIARY_TAX_CATEGORY_ID = 1;

    /**
     * @var Scene
     */
    protected $scene;
    /**
     * @var FactoryMuffin
     */
    private $factory;
    private $entityManager;

    /**
     * SceneBuilder constructor.
     */
    public function __construct(EntityManager $entityManager, FactoryMuffin $factoryMuffin)
    {
        $this->factory = $factoryMuffin;
        $this->entityManager = $entityManager;
    }

    public function reset(): self
    {
        $this->scene = new Scene();

        return $this;
    }

    public function getResult(): Scene
    {
        return $this->scene;
    }

    public function buildAccount(ParameterBag $parameterBag): self
    {
        /* @var $account Account */
        $account = $this->factory->create(Account::class,
            [
                'idFiscal' => ($parameterBag->has('account.idFiscal')) ? $parameterBag->get('account.idFiscal') : self::generateRandomIdFiscal(),
                'owner' => $this->factory->create(User::class),
            ]
        );
        if ($parameterBag->has('account.classification')) {
            $account->setClassification($parameterBag->get('account.classification'));
        }
        $this->scene->setAccount($account);

        return $this;
    }

    public function buildSubsidiary(ParameterBag $parameterBag): self
    {
        /* @var $subsidiary Subsidiary */
        $subsidiary = $this->factory->create(Subsidiary::class, [
                'account' => $this->scene->getAccount(),
            ]
        );
        if ($parameterBag->has('subsidiary.taxCondition')) {
            $subsidiary->setTaxCondition($parameterBag->get('subsidiary.taxCondition'));
        } else {
            $subsidiary->setTaxCondition($this->entityManager->getRepository(TaxCondition::class)->find(
                self::DEFAULT_SUBSIDIARY_TAX_CONDITION_ID
            ));
        }
        if ($parameterBag->has('subsidiary.taxCategoryId')) {
            $subsidiary->setTaxCategory($this->entityManager->getRepository(TaxCategory::class)->find(
                $parameterBag->get('subsidiary.taxCategoryId')
            ));
        } elseif ($parameterBag->has('subsidiary.taxCategory')) {
            $subsidiary->setTaxCategory($parameterBag->get('subsidiary.taxCategory'));
        } else {
            $subsidiary->setTaxCategory($this->entityManager->getRepository(TaxCategory::class)->find(
                self::DEFAULT_SUBSIDIARY_TAX_CATEGORY_ID
            ));
        }

        if ($parameterBag->has('subsidiary.address')) {
            $subsidiary->setTaxCategory($parameterBag->get('subsidiary.address'));
        } elseif ($parameterBag->has('subsidiary.address.province')) {
            $subsidiary->setAddress(
                $this->factory->create(Address::class,
                    ['province' => $parameterBag->get('subsidiary.address.province')])
            );
        } elseif ($parameterBag->has('subsidiary.address.provinceId')) {
            $subsidiary->setAddress(
                $this->factory->create(Address::class,
                    [
                        'province' => $this->entityManager->getRepository(Province::class)->find($parameterBag->get('subsidiary.address.provinceId')),
                    ])
            );
        }
        $this->scene->getAccount()->addSubsidiary($subsidiary);

        return $this;
    }

    public function buildTransaction(ParameterBag $parameterBag): self
    {
        $availableDate = Carbon::now();
        if ($parameterBag->has('transaction.availableDate')) {
            $availableDate = $parameterBag->get('transaction.availableDate');
        } elseif ($this->scene->getDeposit()) {
            $availableDate = $this->scene->getDeposit()->getAvailableDate();
        }

        $subsidiary = $this->scene->getAccount()->getSubsidiaries()->first();

        /* @var $aTransaction Transaction */
        $aTransaction = $this->factory->create(Transaction::class, [
            'subsidiary' => $subsidiary,
            'commission' => ($parameterBag->has('transaction.commission')) ? $parameterBag->get('transaction.commission') : self::DEFAULT_TRANSACTIONS_COMMISSION,
            'commissionTax' => ($parameterBag->has('transaction.commissionTax')) ? $parameterBag->get('transaction.commissionTax') : self::DEFAULT_TRANSACTIONS_COMMISSION_TAX,
            'amount' => ($parameterBag->has('transaction.amount')) ? $parameterBag->get('transaction.amount') : self::DEFAULT_TRANSACTIONS_AMOUNT,
            'availableDate' => $availableDate,
            'typeId' => ($parameterBag->has('transaction.typeId')) ? $parameterBag->get('transaction.typeId') : Transaction::TYPE_SALE,
        ]);

        $paymentMethod = null;
        if ($parameterBag->has('transaction.paymentMethod')) {
            $paymentMethod = $parameterBag->get('transaction.paymentMethod');
        } else {
            $paymentMethod = $this->entityManager->getRepository(PaymentMethod::class)->find(self::DEFAULT_TRANSACTIONS_PAYMENT_METHOD_ID);
        }

        /* @var $aTransactionDetail TransactionDetail */
        $aTransactionDetail = $this->factory->create(TransactionDetail::class,
            [
                'province' => ($parameterBag->has('transaction.province')) ? $parameterBag->get('transaction.province') : $subsidiary->getProvince(),
                'paymentMethod' => $paymentMethod,
                'account' => $this->scene->getAccount(),
                'subsidiary' => $this->scene->getAccount()->getSubsidiaries()->first(),
            ]
        );

        $aTransaction->setTransactionDetail($aTransactionDetail);
        $this->scene->getDeposit()->addTransaction($aTransaction);
        $this->scene->addTransaction($aTransaction);

        return $this;
    }

    /**
     * @return $this
     */
    public function buildDeposit(ParameterBag $parameterBag): self
    {
        $availableDate = Carbon::now();
        if ($parameterBag->has('deposit.availableDate')) {
            $availableDate = $parameterBag->get('deposit.availableDate');
        } elseif ($parameterBag->has('transaction.availableDate')) {
            $availableDate = $parameterBag->get('transaction.availableDate');
        }
        /* @var $deposit Deposit */
        $deposit = $this->factory->create(Deposit::class, array_merge(
                [
                    'account' => $this->scene->getAccount(),
                    'availableDate' => $availableDate,
                ],
                ($parameterBag->has('deposit.amount')) ? [$parameterBag->get('deposit.amount')] : [],
                ($parameterBag->has('deposit.transferredAt')) ? [$parameterBag->get('deposit.transferredAt')] : [])
        );
        $this->scene->setDeposit($deposit);

        return $this;
    }

    public function buildBag(ParameterBag $parameterBag)
    {
        $this->scene->setSaleBag(new SaleBag(
            $this->scene->getTransactions(),
            new Currency($this->scene->getDeposit()->getCurrencyCode()),
            $this->scene->getDeposit()->getAvailableDate()
        ));
    }

    public static function generateRandomIdFiscal()
    {
        return substr(str_replace('.', '', microtime(true)), -10);
    }
}
