<?php

declare(strict_types=1);

use Koabana\Http\Middleware\CsrfMiddleware;
use Koabana\Http\Middleware\SecurityHeadersMiddleware;
use Koabana\Http\Middleware\SessionMiddleware;
use Koabana\Http\Middleware\TrailingSlashMiddleware;

/**
 * Liste des middlewares applicatifs.
 * 
 * Note : ErrorHandlerMiddleware et le Router sont gérés directement dans le Kernel.
 */
return [
    new SessionMiddleware(),
    new CsrfMiddleware(exemptedRoutes: [
        '/api/*',           // Toutes les routes API
        '/webhook/*',       // Webhooks
    ]),
    new TrailingSlashMiddleware(),
    new SecurityHeadersMiddleware(),
];
