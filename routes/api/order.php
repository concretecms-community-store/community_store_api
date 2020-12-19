<?php
defined('C5_EXECUTE') or die("Access Denied.");

$router->get('/orders', '\Concrete\Package\CommunityStoreApi\Api\Order\OrdersController::all')
    ->setScopes('cs:orders:read');

$router->get('/orders/{oID}', '\Concrete\Package\CommunityStoreApi\Api\Order\OrdersController::read')
    ->setRequirement('oID' ,'^[1-9][0-9]{0,9}')
    ->setScopes('cs:orders:read');

$router->patch('/orders/{oID}', '\Concrete\Package\CommunityStoreApi\Api\Order\OrdersController::write')
    ->setRequirement('oID' ,'^[1-9][0-9]{0,9}')
    ->setScopes('cs:orders:write');

$router->get('/fulfilmentstatuses', '\Concrete\Package\CommunityStoreApi\Api\Order\OrdersController::orderStatuses')
    ->setScopes('cs:orders:read');


