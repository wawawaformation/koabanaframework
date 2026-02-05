<?php

declare(strict_types=1);

namespace Koabana\Http\Middleware;

use Koabana\Http\Session\FlashBag;
use Koabana\Http\Session\ProfileBag;
use Koabana\Http\Session\SessionBag;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * SessionMiddleware : gestion de la session PHP.
 *
 * Démarre la session, configure les paramètres sécurisés,
 * et rend disponibles SessionBag, FlashBag et ProfileBag via les attributs de requête.
 */
final class SessionMiddleware implements MiddlewareInterface
{
    /**
     * @param string $sessionName
     * @param int    $lifetime
     */
    public function __construct(
        private readonly string $sessionName = 'KOABANA_SESSION',
        private readonly int $lifetime = 7200, // 2 heures
    ) {}

    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->startSession();

        // Création des bags disponibles dans la requête
        $sessionBag = new SessionBag($_SESSION);
        $flashBag = new FlashBag($sessionBag);
        $profileBag = new ProfileBag($sessionBag);

        // Ajout en tant qu'attributs de requête pour y accéder dans les contrôleurs
        $request = $request
            ->withAttribute('session', $sessionBag)
            ->withAttribute('flash', $flashBag)
            ->withAttribute('profile', $profileBag)
        ;

        $response = $handler->handle($request);

        return $response;
    }

    /**
     * Démarre la session avec des paramètres sécurisés.
     *
     * @return void
     */
    private function startSession(): void
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_secure', '0'); // À mettre à 1 en HTTPS

        session_name($this->sessionName);
        session_set_cookie_params([
            'lifetime' => $this->lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => false, // true en prod HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}
