#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Script de génération de contrôleurs
 * Usage: php bin/make_controller.php NomController
 */

if (\php_sapi_name() !== 'cli') {
    die('Cette commande doit être exécutée en CLI.' . \PHP_EOL);
}

if ($argc < 2) {
    die('Usage: php bin/make_controller.php NomController' . \PHP_EOL);
}

$controllerName = $argv[1];

// Validation du nom
if (!\preg_match('/^[A-Z][a-zA-Z0-9]*Controller$/', $controllerName)) {
    die("❌ Le nom du contrôleur doit être en PascalCase et finir par 'Controller'" . \PHP_EOL);
}

// Chemin racine du projet
$rootPath = \dirname(__DIR__);
$controllerPath = $rootPath . '/src/Controller/' . $controllerName . '.php';
$templateDir = $rootPath . '/views/' . \lcfirst(\str_replace('Controller', '', $controllerName));
$templatePath = $templateDir . '/index.php';

// Vérifications
if (\file_exists($controllerPath)) {
    die("❌ Le contrôleur $controllerName existe déjà !" . \PHP_EOL);
}

if (\file_exists($templatePath)) {
    die("❌ Le template existe déjà !" . \PHP_EOL);
}

// Génération du contrôleur
$controllerCode = generateControllerCode($controllerName);

if (!\is_dir(\dirname($controllerPath))) {
    \mkdir(\dirname($controllerPath), 0755, true);
}

if (!\file_put_contents($controllerPath, $controllerCode)) {
    die("❌ Erreur lors de la création du contrôleur!" . \PHP_EOL);
}

echo "✅ Contrôleur créé : $controllerPath" . \PHP_EOL;

// Génération du template
$templateCode = generateTemplateCode($controllerName);

if (!\is_dir($templateDir)) {
    \mkdir($templateDir, 0755, true);
}

if (!\file_put_contents($templatePath, $templateCode)) {
    die("❌ Erreur lors de la création du template!" . \PHP_EOL);
}

echo "✅ Template créé : $templatePath" . \PHP_EOL;

// Ajout de la route
addRoute($controllerName, $rootPath);

echo "\n✨ Prêt à l'emploi !" . \PHP_EOL;

/**
 * Génère le code du contrôleur
 */
function generateControllerCode(string $controllerName): string
{
    $shortName = \str_replace('Controller', '', $controllerName);
    $routeName = \strtolower(\preg_replace('/([a-z])([A-Z])/', '$1-$2', $shortName));

    return <<<PHP
<?php

declare(strict_types=1);

namespace Koabana\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * $controllerName
 */
final class $controllerName extends AbstractController
{
    public function index(ServerRequestInterface \$request): ResponseInterface
    {
        return \$this->render(\$request, '$shortName/index', [
            'title' => '$shortName',
        ]);
    }
}
PHP;
}

/**
 * Génère le code du template
 */
function generateTemplateCode(string $controllerName): string
{
    $shortName = \str_replace('Controller', '', $controllerName);

    return <<<PHP
<?php
/**
 * Template : $shortName/index
 */
?>

<h1><?= htmlspecialchars(\$title) ?></h1>

<p>Bienvenue dans $shortName !</p>
PHP;
}

/**
 * Ajoute la route au fichier config/routes.php
 */
function addRoute(string $controllerName, string $rootPath): void
{
    $routesFile = $rootPath . '/config/routes.php';
    $shortName = \str_replace('Controller', '', $controllerName);
    $routeName = \strtolower(\preg_replace('/([a-z])([A-Z])/', '$1-$2', $shortName));

    if (!\file_exists($routesFile)) {
        return;
    }

    $content = \file_get_contents($routesFile);

    // Cherche la dernière route
    if (\preg_match('/\[\s*\'GET\',\s*\'[^\']*\',\s*\[[^\]]+\]\s*\],?/', $content, $matches, \PREG_OFFSET_CAPTURE)) {
        $lastRouteEnd = $matches[0][1] + \strlen($matches[0][0]);

        $newRoute = "\n    ['GET', '/$routeName', [\\Koabana\\Controller\\$controllerName::class, 'index']],";
        $newContent = \substr_replace($content, $newRoute, $lastRouteEnd, 0);

        \file_put_contents($routesFile, $newContent);

        echo "✅ Route ajoutée : GET /$routeName" . \PHP_EOL;
    }
}
