<?php

declare(strict_types=1);

namespace Koabana\Http\Middleware;

use GuzzleHttp\Psr7\Response;
use Koabana\Http\Session\SessionBag;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CsrfMiddleware : protection contre les attaques CSRF.
 *
 * Génère un token CSRF en session et le valide sur les requêtes mutantes (POST, PUT, PATCH, DELETE).
 * Permet d'exempter certaines routes (webhooks, API publiques...).
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    private const TOKEN_KEY = '_csrf_token';
    private const FORM_FIELD = '_csrf_token';
    private const HEADER_NAME = 'X-CSRF-Token';

    /**
     * @param array<string> $exemptedRoutes Routes exemptées (ex: ['/api/webhook', '/api/public/*'])
     */
    public function __construct(
        private readonly array $exemptedRoutes = [],
    ) {}

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var null|SessionBag $session */
        $session = $request->getAttribute('session');

        if (!$session instanceof SessionBag) {
            throw new \RuntimeException('SessionMiddleware doit être exécuté avant CsrfMiddleware');
        }

        // Génère ou récupère le token
        $token = $this->getToken($session);

        // Injecte le token dans la requête pour utilisation dans les vues
        $request = $request->withAttribute('csrf_token', $token);

        // Vérification uniquement sur méthodes mutantes
        $method = strtoupper($request->getMethod());
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $path = $request->getUri()->getPath();

            // Exemption de routes
            if (!$this->isExempted($path)) {
                if (!$this->validateToken($request, $token)) {
                    return new Response(
                        403,
                        ['Content-Type' => 'text/plain; charset=utf-8'],
                        'Token CSRF invalide ou manquant',
                    );
                }
            }
        }

        return $handler->handle($request);
    }

    /**
     * Récupère ou génère le token CSRF en session.
     *
     * @param SessionBag $session
     *
     * @return string
     */
    private function getToken(SessionBag $session): string
    {
        $token = $session->get(self::TOKEN_KEY);

        if (!is_string($token) || '' === $token) {
            $token = bin2hex(random_bytes(32));
            $session->set(self::TOKEN_KEY, $token);
        }

        return $token;
    }

    /**
     * Valide le token CSRF depuis le formulaire ou le header.
     *
     * @param ServerRequestInterface $request
     * @param string                 $expectedToken
     *
     * @return bool
     */
    private function validateToken(ServerRequestInterface $request, string $expectedToken): bool
    {
        // 1. Vérifier dans le header
        $headerToken = $request->getHeaderLine(self::HEADER_NAME);
        if ('' !== $headerToken && hash_equals($expectedToken, $headerToken)) {
            return true;
        }

        // 2. Vérifier dans le body (form-data ou JSON)
        $parsedBody = $request->getParsedBody();

        if (is_array($parsedBody) && isset($parsedBody[self::FORM_FIELD])) {
            $formToken = (string) $parsedBody[self::FORM_FIELD];

            return hash_equals($expectedToken, $formToken);
        }

        return false;
    }

    /**
     * Vérifie si une route est exemptée de la vérification CSRF.
     *
     * @param string $path
     *
     * @return bool
     */
    private function isExempted(string $path): bool
    {
        foreach ($this->exemptedRoutes as $pattern) {
            // Support wildcard basique
            $regex = str_replace('\*', '.*', preg_quote($pattern, '/'));
            if (preg_match('/^'.$regex.'$/', $path)) {
                return true;
            }
        }

        return false;
    }
}
