<?php
namespace Concrete\Package\CommunityStoreApi;

use Concrete\Core\Http\Middleware\ApiLoggerMiddleware;
use Concrete\Core\Http\Middleware\FractalNegotiatorMiddleware;
use Concrete\Core\Http\Middleware\OAuthAuthenticationMiddleware;
use Concrete\Core\Http\Middleware\OAuthErrorMiddleware;
use Concrete\Core\Routing\RouteListInterface;
use Concrete\Core\Routing\Router;

class RouteList implements RouteListInterface
{
    public function loadRoutes(Router $router)
    {
        $api = $router->buildGroup()
            ->setPrefix('/cs/api/v1')
            ->addMiddleware(OAuthErrorMiddleware::class)
            ->addMiddleware(OAuthAuthenticationMiddleware::class)
            ->addMiddleware(FractalNegotiatorMiddleware::class);

        // The ApiLoggerMiddleware needs to have high priority than the OAuthAuthenticationMiddleware
        $app = \Concrete\Core\Support\Facade\Application::getFacadeApplication();
        if ($app->make('config')->get('concrete.log.api')) {
            $api->addMiddleware(ApiLoggerMiddleware::class, 9);
        }

        $api->routes('api/order.php', 'community_store_api');
        $api->routes('api/product.php', 'community_store_api');
    }
}