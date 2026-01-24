<?php

declare(strict_types=1);

namespace Koabana\Http\Middleware;

use GuzzleHttp\Psr7\Response;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
       
            return $handler->handle($request);
        
    }

    /**
     * @param int $status
     * @param string $body
     * @return ResponseInterface
     */
    private function html(int $status, string $body): ResponseInterface
    {
        return new Response(
            $status,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $body
        );
    }
}
