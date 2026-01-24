<?php

declare(strict_types=1);

namespace Koabana\Controller;

use GuzzleHttp\Psr7\Response;
use Koabana\Model\Repository\DemoRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class DemoController
{
    public function __construct(private DemoRepository $demoRepository, private LoggerInterface $logger) {}

    /**
     * @param array<string, string> $args
     */
    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $html = '<h1>Koabana</h1><p>It works.</p>';

        $users = $this->demoRepository->findAll();

        $html .= '<h2>Users:</h2><ul>';
        foreach ($users as $user) {
            $html .= '<li>'.htmlspecialchars($user['display_name']).' ('.htmlspecialchars($user['email']).')</li>';
        }
        $html .= '</ul>';

        $this->logger->error('Test log depuis DemoController');

        return new Response(
            200,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $html,
        );
    }
}
