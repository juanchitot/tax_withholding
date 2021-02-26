<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Integration\Service;

use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use Symfony\Component\HttpFoundation\ParameterBag;

class DecoratedConfigurationManager implements ConfigurationManagerInterface
{
    const FEATURES_CONFIGURATION_PREFIX = 'feature.';

    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;
    /**
     * @var ParameterBag
     */
    private $overwrittenConfigurations;

    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
        $this->overwrittenConfigurations = new ParameterBag();
    }

    public function getConfiguration()
    {
        return $this->configurationManager->getConfiguration();
    }

    public function get(string $parameter)
    {
        if ($this->overwrittenConfigurations->has($parameter)) {
            return $this->overwrittenConfigurations->get($parameter);
        }

        return $this->configurationManager->get($parameter);
    }

    public function isFeatureEnabled(string $feature): bool
    {
        if ($this->overwrittenConfigurations->has(self::FEATURES_CONFIGURATION_PREFIX.$feature)) {
            return $this->overwrittenConfigurations->get(self::FEATURES_CONFIGURATION_PREFIX.$feature);
        }

        return $this->configurationManager->isFeatureEnabled($feature);
    }

    public function set(string $key, $value)
    {
        $this->overwrittenConfigurations->set($key, $value);
    }
}
