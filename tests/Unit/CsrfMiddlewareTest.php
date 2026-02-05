<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Koabana\Http\Middleware\CsrfMiddleware;
use Koabana\Http\Session\SessionBag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class CsrfMiddlewareTest extends TestCase
{
    private CsrfMiddleware $middleware;
    private RequestHandlerInterface $handler;
    private SessionBag $session;

    /** @var array<string, mixed> */
    private array $sessionData = [];

    protected function setUp(): void
    {
        $this->middleware = new CsrfMiddleware(['/api/*']);
        $this->session = new SessionBag($this->sessionData);

        /** @var MockObject&RequestHandlerInterface */
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response(200, [], 'OK'));
        $this->handler = $handler;
    }

    public function testGetRequestBypassesCsrfCheck(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);

        $response = $this->middleware->process($request, $this->handler);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testTokenIsGeneratedForSession(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);

        $response = $this->middleware->process($request, $this->handler);

        self::assertTrue($this->session->has('_csrf_token'));
    }

    public function testTokenIsHexadecimal(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);

        $this->middleware->process($request, $this->handler);

        $token = $this->session->get('_csrf_token');
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    public function testPostRequestWithValidTokenPasses(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);
        $this->middleware->process($request, $this->handler);

        $token = $this->session->get('_csrf_token');

        $postRequest = new ServerRequest(
            'POST',
            'http://localhost/form',
            ['X-CSRF-Token' => $token],
        );
        $postRequest = $postRequest->withAttribute('session', $this->session);

        $response = $this->middleware->process($postRequest, $this->handler);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testPostRequestWithInvalidTokenReturns403(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);
        $this->middleware->process($request, $this->handler);

        $postRequest = new ServerRequest(
            'POST',
            'http://localhost/form',
            ['X-CSRF-Token' => 'invalid_token'],
        );
        $postRequest = $postRequest->withAttribute('session', $this->session);

        $response = $this->middleware->process($postRequest, $this->handler);

        self::assertEquals(403, $response->getStatusCode());
    }

    public function testExemptedPathsSkipCsrfValidation(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);
        $this->middleware->process($request, $this->handler);

        $postRequest = new ServerRequest(
            'POST',
            'http://localhost/api/endpoint',
            ['X-CSRF-Token' => 'invalid_token'],
        );
        $postRequest = $postRequest->withAttribute('session', $this->session);

        $response = $this->middleware->process($postRequest, $this->handler);

        // Avec exemption /api/*, le token invalide ne cause pas de 403
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testTokenFromFormBodyWorks(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $request = $request->withAttribute('session', $this->session);
        $this->middleware->process($request, $this->handler);

        $token = $this->session->get('_csrf_token');

        $postRequest = new ServerRequest(
            'POST',
            'http://localhost/form',
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            '_csrf_token='.urlencode($token).'&name=test',
        );
        $postRequest = $postRequest->withAttribute('session', $this->session);
        $postRequest = $postRequest->withParsedBody(['_csrf_token' => $token, 'name' => 'test']);

        $response = $this->middleware->process($postRequest, $this->handler);

        self::assertEquals(200, $response->getStatusCode());
    }
}
