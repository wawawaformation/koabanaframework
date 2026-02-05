<?php

declare(strict_types=1);

namespace Koabana\Http;

use Koabana\Http\Middleware\ErrorHandlerMiddleware;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Relay\Relay;

/**
 * Noyau HTTP : compose la stack de middlewares et délègue au routeur.
 */
final class Kernel implements RequestHandlerInterface
{
    private Relay $relay;

    /**
     * @param Router          $router
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Router $router,
        private readonly LoggerInterface $logger,
    ) {
        $queue = require __DIR__.'/../../config/middlewares.php';
        $appEnv = (string) (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'prod'));
        $displayErrors = 'prod' !== $appEnv;

        array_unshift($queue, new ErrorHandlerMiddleware($this->logger, $displayErrors));

        $queue[] = new class($this->router) implements MiddlewareInterface {
            public function __construct(private readonly Router $router) {}

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                return $this->router->dispatch($request);
            }
        };

        $this->relay = new Relay($queue);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->relay->handle($request);
    }
}
