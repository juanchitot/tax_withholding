<?php

namespace GeoPagos\WithholdingTaxBundle\EventSubscriber;

use GeoPagos\ApiBundle\Entity\BackOfficeUser;
use GeoPagos\ApiBundle\Repository\BackOfficeUserRepository;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\CommonBackBundle\Block\MenuItemBlock;
use Sonata\BlockBundle\Event\BlockEvent;
use Sonata\BlockBundle\Model\Block;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class AddWithholdingSidebarMenuItemsSubscriber implements EventSubscriberInterface
{
    public static $isFeatureEnabled = false;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var BackOfficeUserRepository */
    private $backOfficeUserRepository;

    /** @var ConfigurationManager */
    private $configurationManager;

    /** @var RequestStack */
    private $requestStack;

    public function __construct(
        ConfigurationManager $configurationManager,
        TokenStorageInterface $tokenStorage,
        BackOfficeUserRepository $backOfficeUserRepository,
        RequestStack $requestStack
    ) {
        self::$isFeatureEnabled = false;

        if (
            $configurationManager->isFeatureEnabled('process_iibb') ||
            $configurationManager->isFeatureEnabled('process_vat') ||
            $configurationManager->isFeatureEnabled('process_income_tax') ||
            $configurationManager->isFeatureEnabled('process_itbis')
        ) {
            self::$isFeatureEnabled = true;
        }

        $this->configurationManager = $configurationManager;
        $this->tokenStorage = $tokenStorage;
        $this->backOfficeUserRepository = $backOfficeUserRepository;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents()
    {
        return [
            BlockEvent::class => 'onBlock',
        ];
    }

    private $items = [
        [
            'url' => 'withholding_tax_register_province_backoffice_section',
            'icon' => 'fa-database',
            'label' => 'modules.withholding_tax_registry',
            'rule' => 'withholding_tax_register_province.view',
        ],
    ];

    public function onBlock(BlockEvent $event)
    {
        if (!self::$isFeatureEnabled) {
            return;
        }

        $wtrVersion = $this->requestStack->getCurrentRequest()
            ->cookies->get('wtr_version', 'v2');
        if ('v1' === $wtrVersion) {
            $this->items[] = [
                'url' => 'withholding_tax_rule_backoffice_section',
                'icon' => 'fa-edit',
                'label' => 'modules.withholding_tax_rule',
                'rule' => 'withholding_tax.view',
                'only_ultra_admin' => true,
            ];
        } else {
            $this->items[] = [
                'url' => 'withholding_tax_rule_backoffice_section_v2',
                'icon' => 'fa-edit',
                'label' => 'modules.withholding_tax_rule',
                'rule' => 'withholding_tax.view',
                'only_ultra_admin' => true,
            ];
        }

        foreach ($this->items as $item) {
            if ($this->showOnlyForUltraAdmin($item)) {
                $block = $this->makeBlock($event->getSettings(), $item);
                $event->addBlock($block);
            }
        }
    }

    private function makeBlock(array $settings, $item): Block
    {
        return new MenuItemBlock(
            $settings,
            $item['url'],
            $item['icon'],
            $item['label'],
            $item['rule']
        );
    }

    public function getUser(): BackOfficeUser
    {
        return $this->tokenStorage->getToken()->getUser();
    }

    public function showOnlyForUltraAdmin($item): bool
    {
        return (!empty($item['only_ultra_admin']) && !$this->getUser()->getUltraAdmin()) ? false : true;
    }
}
