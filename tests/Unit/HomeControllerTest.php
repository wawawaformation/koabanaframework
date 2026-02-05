<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\Controller\HomeController;
use Koabana\Model\Repository\TestRepository;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class HomeControllerTest extends TestCase
{
    private HomeController $controller;

    protected function setUp(): void
    {
        /** @var TestRepository&\PHPUnit\Framework\MockObject\MockObject */
        $testRepository = $this->createMock(TestRepository::class);
        $testRepository->method('findAll')->willReturn([]);
        
        $this->controller = new HomeController($testRepository);
    }

    public function testIndexReturnsResponseWithStatus200(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $response = $this->controller->index($request, []);

        self::assertEquals(200, $response->getStatusCode());
    }

    public function testIndexReturnsHtmlContentType(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $response = $this->controller->index($request, []);

        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
    }

    public function testIndexReturnsResponseInterface(): void
    {
        $request = new ServerRequest('GET', 'http://localhost/');
        $response = $this->controller->index($request, []);

        self::assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $response);
    }
}
