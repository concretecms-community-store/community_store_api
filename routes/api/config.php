<?php
defined('C5_EXECUTE') or die("Access Denied.");

/**
 * @var Concrete\Core\Routing\RouteGroupBuilder $this
 * @var Concrete\Core\Application\Application $app
 * @var Concrete\Core\Routing\Router $router
 */

$router->get('/config', 'Concrete\Package\CommunityStoreApi\Api\Config\ConfigController::read')
    ->setScopes('cs:config:read')
;
