<?php

declare(strict_types=1);

namespace Koabana\Bootstrap;

use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;
use Psr\Container\ContainerInterface;

final class RouterFactory
{
    public static function create(ContainerInterface $container, string $routesFile): Router
    {
        $strategy = new ApplicationStrategy();
        $strategy->setContainer($container);

        $router = new Router();
        $router->setStrategy($strategy);

        /** @var array<int, array{0:string,1:string,2:mixed}> $routes */
        $routes = require $routesFile;

        foreach ($routes as $route) {
            [$method, $path, $handler] = $route;
            $router->map($method, $path, $handler);
        }

        return $router;
    }
}
