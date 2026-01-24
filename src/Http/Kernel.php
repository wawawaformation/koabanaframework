<?php

declare(strict_types=1);

namespace Koabana\Http;

use Koabana\Http\Middleware\ErrorHandlerMiddleware;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\Relay;

final class Kernel implements RequestHandlerInterface
{
    private Relay $relay;

    public function __construct(
        private readonly Router $router,
    ) {
        $queue = [
            new ErrorHandlerMiddleware(),

            // Middleware terminal : le routeur
            new class($this->router) implements MiddlewareInterface {
                public function __construct(private readonly Router $router) {}

                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler,
                ): ResponseInterface {
                    return $this->router->dispatch($request);
                }
            },
        ];

        $this->relay = new Relay($queue);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->relay->handle($request);
    }
}
