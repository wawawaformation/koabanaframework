<?php

declare(strict_types=1);

namespace Tests\Unit;

use GuzzleHttp\Psr7\ServerRequest;
use Koabana\Controller\DemoController;
use Koabana\Database\BDDFactory;
use Koabana\Model\Repository\DemoRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class DemoControllerTest extends TestCase
{
    private DemoController $controller;
    private DemoRepository $repository;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        /** @var BDDFactory&MockObject */
        $bddFactory = $this->createMock(BDDFactory::class);
        $this->repository = new DemoRepository($bddFactory);

        /** @var LoggerInterface&MockObject */
        $logger = $this->createMock(LoggerInterface::class);
        $this->logger = $logger;
        $this->controller = new DemoController($this->repository, $this->logger);
    }

    public function testInvokeReturnsResponse200(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/demo');
        $response = $this->controller->__invoke($request, []);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testInvokeReturnsHtmlContentType(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/demo');
        $response = $this->controller->__invoke($request, []);

        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testFormReturnsResponse200(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/demo/form');
        $response = $this->controller->form($request, []);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testFormIncludesCsrfToken(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/demo/form');
        $request = $request->withAttribute('csrf_token', 'test_token_123');
        $response = $this->controller->form($request, []);

        $body = (string) $response->getBody();
        self::assertStringContainsString('test_token_123', $body);
        self::assertStringContainsString('_csrf_token', $body);
    }

    public function testSubmitWithValidDataRedirects(): void
    {
        $request = new ServerRequest('POST', 'http://localhost/demo/submit');
        $request = $request->withParsedBody(['name' => 'John', 'email' => 'john@example.com']);
        $response = $this->controller->submit($request, []);

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/demo/form', $response->getHeader('Location')[0]);
    }

    public function testTestBagsReturnsResponse200(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/demo/tests');
        $response = $this->controller->testBags($request);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testSessionSetRedirects(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/demo/session/set');
        $response = $this->controller->sessionSet($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/demo/session/view', $response->getHeader('Location')[0]);
    }

    public function testProfileLoginRedirects(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/demo/profile/login');
        $response = $this->controller->profileLogin($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/demo/tests', $response->getHeader('Location')[0]);
    }

    public function testProfileLogoutRedirects(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/demo/profile/logout');
        $response = $this->controller->profileLogout($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/demo/tests', $response->getHeader('Location')[0]);
    }

    public function testFlashAddRedirects(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/demo/flash/add');
        $response = $this->controller->flashAdd($request);

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/demo/tests', $response->getHeader('Location')[0]);
    }
}
