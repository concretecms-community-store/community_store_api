<?php
namespace Concrete\Package\CommunityStoreApi\Api\Order;
use Concrete\Package\CommunityStore\Src\CommunityStore\Order\OrderList as CSOrderList;

class OrderList extends CSOrderList
{
    protected $paginationPageParameter = 'page';

}