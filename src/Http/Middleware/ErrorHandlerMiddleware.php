<?php

declare(strict_types=1);

namespace Koabana\Http\Middleware;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * Intercepte les exceptions, logge et affiche une page d'erreur en mode dev.
 */
final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    /**
     * @param LoggerInterface $logger
     * @param bool            $displayErrors
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly bool $displayErrors,
    ) {}

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $exception) {
            $this->logger->error('Unhandled exception', [
                'exception' => $exception,
            ]);

            if ($this->displayErrors) {
                $whoops = new Run();
                $whoops->writeToOutput(false);
                $whoops->allowQuit(false);
                $whoops->pushHandler(new PrettyPageHandler());
                $html = $whoops->handleException($exception);

                return new Response(
                    500,
                    ['Content-Type' => 'text/html; charset=utf-8'],
                    $html,
                );
            }

            return new Response(
                500,
                ['Content-Type' => 'text/plain; charset=utf-8'],
                'Erreur interne du serveur',
            );
        }
    }
}
