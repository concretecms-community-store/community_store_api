<?php

namespace Concrete\Package\CommunityStoreApi\Api\Order\OrderStatus;

use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderStatus\OrderStatus;
use League\Fractal\TransformerAbstract;

class OrderStatusTransformer extends TransformerAbstract
{
    /**
     * Basic transforming of a OrderStatus into an array
     *
     * @param OrderStatus $orderStatus
     * @return array
     */
    public function transform(OrderStatus $orderStatus)
    {
        $data = ['id' => $orderStatus->getID(),
            'handle' => $orderStatus->getHandle(),
            'name' => $orderStatus->getName()
        ];

        return $data;
    }
}