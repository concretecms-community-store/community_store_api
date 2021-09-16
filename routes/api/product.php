<?php
defined('C5_EXECUTE') or die("Access Denied.");

$router->get('/products', '\Concrete\Package\CommunityStoreApi\Api\Product\ProductsController::all')
    ->setScopes('cs:products:read');

$router->get('/products/{pID}', '\Concrete\Package\CommunityStoreApi\Api\Product\ProductsController::read')
    ->setRequirement('pID' ,'^[1-9][0-9]{0,9}')
    ->setScopes('cs:products:read');

$router->patch('/products/{pID}', '\Concrete\Package\CommunityStoreApi\Api\Product\ProductsController::write')
    ->setRequirement('pID' ,'^[1-9][0-9]{0,9}')
    ->setScopes('cs:products:write');

$router->get('/skus/{sku}', '\Concrete\Package\CommunityStoreApi\Api\Product\ProductsController::stockLevelReadSku')
    ->setScopes('cs:products:read');

$router->patch('/skus/{sku}', '\Concrete\Package\CommunityStoreApi\Api\Product\ProductsController::stockLevelWriteSku')
    ->setScopes('cs:products:write');

$router->get('/variations/{id}', '\Concrete\Package\CommunityStoreApi\Api\Product\ProductsController::stockLevelReadVariation')
    ->setScopes('cs:products:read');

$router->patch('/variations/{id}', '\Concrete\Package\CommunityStoreApi\Api\Product\ProductsController::stockLevelWriteVariation')
    ->setScopes('cs:products:write');


