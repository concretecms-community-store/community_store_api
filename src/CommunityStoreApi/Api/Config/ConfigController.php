<?php

namespace Concrete\Package\CommunityStoreApi\Api\Config;

use Concrete\Core\Api\ApiController;
use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Package\PackageService;
use Concrete\Package\CommunityStore\Src\CommunityStore\Utilities\Price;
use Punic\Currency;
use Punic\Data;

class ConfigController extends ApiController
{
    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    /**
     * @var \Concrete\Core\Package\PackageService
     */
    protected $packageService;

    public function __construct(Repository $config, PackageService $packageService)
    {
        $this->config = $config;
        $this->packageService = $packageService;
    }

    /**
     * Get the Community Store configuration.
     *
     * @return \League\Fractal\Resource\Item|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function read()
    {
        $currencyCode = (string) $this->config->get('community_store.currency');
        $csPackage = $this->packageService->getByHandle('community_store');
        $csApiPackage = $this->packageService->getByHandle('community_store_api');
        $config = [
            'currency' => [
                'code' => $currencyCode,
                'symbol' => $this->getCurrencySymbol($currencyCode),
                'decimal_digits' => $this->getDecimalDigits($currencyCode),
            ],
            'community_store' => [
                'version' => $csPackage->getPackageVersion(),
            ],
            'community_store_api' => [
                'version' => $csApiPackage->getPackageVersion(),
            ],
            'system' => [
                'time_zone' => (string) date_default_timezone_get(),
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
