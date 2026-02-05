<?php

declare(strict_types=1);

namespace Koabana\Http\Middleware;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Classe permettant de supprimer le dernier slash de l'url sauf si la route est celle par defaut.
 */
/*final class TrainingSlashMiddleware implements MiddlewareInterface. Premiere version ecrite par Benjamin.
{


      Supprime le slash final de l'URL si ce n'est pas la route par défaut.

      @param ServerRequestInterface $request la requete entrante
      @param RequestHandlerInterface $handler le manche de Middlewrae queue
      @return ResponseInterface la reponse

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if ('/' !== $path && str_ends_with($path, '/')) {
            $newPath = rtrim($path, '/');
            $uri = $request->getUri()->withPath($newPath);

            return new Response(308, ['Location' => (string) $uri]);
        }

        return $handler->handle($request);
    }

}*/

/**
 * Supprime le trailing slash de l'URL (sauf pour la racine "/").
 * Exemple : "/contact/" -> "/contact"
 */
final class TrailingSlashMiddleware implements MiddlewareInterface
{
    /**
     * fonction qui traite la requête entrante.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return Response|ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        // Ne touche jamais la racine (ni un path vide)
        if ('' === $path || '/' === $path) {
            return $handler->handle($request);
        }

        // Si pas de slash final, on continue
        if (!\str_ends_with($path, '/')) {
            return $handler->handle($request);
        }

        // Supprime uniquement 1 slash final
        $newPath = \substr($path, 0, -1);

        // Sécurité : si ça devient vide, on retombe sur "/"
        if ('' === $newPath) {
            $newPath = '/';
        }

        $newUri = $uri->withPath($newPath);

        return new Response(308, [
            'Location' => (string) $newUri,
            'Content-Length' => '0',
        ]);
    }
}
