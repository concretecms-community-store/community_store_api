<?php

namespace Concrete\Package\CommunityStoreApi\Api\Config;

use Concrete\Core\Api\ApiController;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Price;
use Punic\Currency;
use Punic\Data;

class ConfigController extends ApiController
{

    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Get the Community Store configuration.
     *
     * @return \League\Fractal\Resource\Item|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function read()
    {
        $currencyCode = (string) $this->config->get('community_store.currency');
        $config = [
            'currency' => [
                'code' => $currencyCode,
                'symbol' => $this->getCurrencySymbol($currencyCode),
                'decimal_digits' => $this->getDecimalDigits($currencyCode),
            ],
        ];

        return $this->transform($config, new ConfigTransformer());
    }

    /**
     * @param string $currencyCode
     *
     * @return string
     */
    private function getCurrencySymbol($currencyCode)
    {
        $symbol = Currency::getSymbol($currencyCode, '', 'en-US');

        return $symbol === '' ? (string) $this->config->get('community_store.symbol') : $symbol;
    }

    /**
     * @param string $currencyCode
     *
     * @return int
     */
    private function getDecimalDigits($currencyCode)
    {
        if ($currencyCode === '') {
            return Price::isZeroDecimalCurrency($currencyCode) ? 2 : 0;
        }
        $currencyData = Data::getGeneric('currencyData');
        if (isset($currencyData['fractions'][$currencyCode]['digits'])) {
            return $currencyData['fractions'][$currencyCode]['digits'];
        }

        return $currencyData['fractionsDefault']['digits'];
    }
}
