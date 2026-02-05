<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Koabana\Http\Middleware\TrailingSlashMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class TrailingSlashMiddlewareTest extends TestCase
{
    private TrailingSlashMiddleware $middleware;
    private RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->middleware = new TrailingSlashMiddleware();

        /** @var MockObject&RequestHandlerInterface */
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response(200, [], 'OK'));
        $this->handler = $handler;
    }

    public function testRootPathIsPreserved(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $response = $this->middleware->process($request, $this->handler);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testPathWithoutTrailingSlashIsPassedThrough(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/about');
        $response = $this->middleware->process($request, $this->handler);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testPathWithTrailingSlashRedirects(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/about/');
        $response = $this->middleware->process($request, $this->handler);

        self::assertEquals(308, $response->getStatusCode());
        self::assertEquals('http://localhost/about', $response->getHeader('Location')[0]);
    }

    public function testDeeplyNestedPathWithTrailingSlashRedirects(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/path/to/page/');
        $response = $this->middleware->process($request, $this->handler);

        self::assertEquals(308, $response->getStatusCode());
        self::assertEquals('http://localhost/path/to/page', $response->getHeader('Location')[0]);
    }

    public function testQueryStringIsPreservedInRedirect(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/about/?page=1&sort=name');
        $response = $this->middleware->process($request, $this->handler);

        self::assertEquals(308, $response->getStatusCode());
        $location = $response->getHeader('Location')[0];
        // Vérifier que la redirection contient le chemin et la query
        self::assertStringContainsString('/about', $location);
        self::assertStringContainsString('page=1', $location);
        self::assertStringContainsString('sort=name', $location);
    }
}
