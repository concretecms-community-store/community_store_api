<?php

namespace Concrete\Package\CommunityStoreApi\Api\Config;

use League\Fractal\TransformerAbstract;

class ConfigTransformer extends TransformerAbstract
{
    public function transform(array $config)
    {
        // No transformation is required so far
        return $config;
    }
}
