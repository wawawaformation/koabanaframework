<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Http\Middleware\SecurityHeadersMiddleware;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

final class SecurityHeadersMiddlewareTest extends TestCase
{
    private SecurityHeadersMiddleware $middleware;
    private RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->middleware = new SecurityHeadersMiddleware();
        /** @var RequestHandlerInterface&\PHPUnit\Framework\MockObject\MockObject */
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response(200, [], 'OK'));
        $this->handler = $handler;
    }

    public function testAddsContentSecurityPolicyHeader(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $response = $this->middleware->process($request, $this->handler);

        self::assertTrue($response->hasHeader('Content-Security-Policy'));
    }

    public function testAddsXFrameOptionsHeader(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $response = $this->middleware->process($request, $this->handler);

        self::assertTrue($response->hasHeader('X-Frame-Options'));
        self::assertEquals('DENY', $response->getHeader('X-Frame-Options')[0]);
    }

    public function testAddsReferrerPolicyHeader(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $response = $this->middleware->process($request, $this->handler);

        self::assertTrue($response->hasHeader('Referrer-Policy'));
    }

    public function testAddsPermissionsPolicyHeader(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $response = $this->middleware->process($request, $this->handler);

        self::assertTrue($response->hasHeader('Permissions-Policy'));
    }

    public function testHttpsWithProdEnvAddsHSTS(): void
    {
        putenv('APP_ENV=prod');
        $this->middleware = new SecurityHeadersMiddleware();
        
        $request = new ServerRequest('GET', 'https://localhost/');
        $response = $this->middleware->process($request, $this->handler);

        self::assertTrue($response->hasHeader('Strict-Transport-Security'));
        
        putenv('APP_ENV=dev');
    }

    public function testHttpWithDevEnvDoesNotAddHSTS(): void
    {
        putenv('APP_ENV=dev');
        $this->middleware = new SecurityHeadersMiddleware();
        
        $request = new ServerRequest('GET', 'http://localhost/');
        $response = $this->middleware->process($request, $this->handler);

        self::assertFalse($response->hasHeader('Strict-Transport-Security'));
    }

    public function testCspHeaderContainsCommonDirectives(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $response = $this->middleware->process($request, $this->handler);

        $csp = $response->getHeader('Content-Security-Policy')[0];
        self::assertStringContainsString("default-src 'self'", $csp);
        self::assertStringContainsString("object-src 'none'", $csp);
    }
}
