<?php

declare(strict_types=1);

namespace Koabana\Controller;

use GuzzleHttp\Psr7\Response;
use Koabana\Model\Repository\TestRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Contrôleur de la page d'accueil.
 */
final class HomeController
{
    /**
     * @param TestRepository $testRepository
     */
    public function __construct(private TestRepository $testRepository) {}

    /**
     * @param array<string, string> $args
     *
     * @return ResponseInterface
     */
    public function index(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $response = $this->testRepository->findAll();

        $html = '<h1>Welcome to Koabana Framework</h1><p>This is the home page.</p>';

        return new Response(
            200,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $html,
        );
    }
}
