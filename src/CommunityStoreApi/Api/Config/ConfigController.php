<?php

namespace Concrete\Package\CommunityStoreApi\Api\Config;

use Concrete\Core\Api\ApiController;
use Concrete\Core\Config\Repository\Repository;

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
        $config = [
            'currency' => (string) $this->config->get('community_store.currency'),
        ];

        return $this->transform($config, new ConfigTransformer());
    }
}
