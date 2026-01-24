# Koabana Framework (squelette minimal PSR)

Squelette de framework PHP volontairement minimal, agnostique de la stack (Apache/Nginx, Docker ou non), basé sur des composants PSR.
Objectif : fournir une tuyauterie propre (HTTP + routing + DI + middleware + erreurs + logs + DB) réutilisable dans vos projets futurs.

## Principes
- Pas de code métier ici : uniquement de l’infrastructure réutilisable.
- Dépendances injectées via DI (pas de `new` partout).
- Connexion DB à la demande (pas dans les constructeurs).
- Compatible avec n’importe quel hébergement/stack (Docker optionnel côté projets).

## Prérequis
- PHP >= 8  Pour information, le framework a été développé et testé avec PHP 8.4.17
- Composer

## Installation

### En développement (avant Packagist) via repository VCS
Dans le projet qui consomme le framework :

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://github.com/wawawaformation/KoabanaFramework.git" }
  ],
  "require": {
    "wawawaformation/KoabanaFramework": "dev-main"
  }
}

```

Puis :

Puis :

```bash
composer install
```

### Plus tard (avec Packagist)

```bash
composer require <vendor>/<package>
```

## Structure (indicative)

- `public/` : front controller (`index.php`), point d’entrée HTTP
- `src/` : code framework (HTTP, DI, routing, middleware, DB, logs…)
- `config/` : configuration (containers, routes, etc.)
- `tests/` : tests
- `var/` : runtime (logs/cache) — ignoré par Git

## Configuration (.env)

Ce dépôt ne versionne pas `.env` (secrets). Utilisez `.env.example` comme modèle.

Variables typiques :

```dotenv
APP_ENV=dev
APP_DEBUG=1

DB_DSN=mysql:host=localhost;port=3306;dbname=app;charset=utf8mb4
DB_USER=app
DB_PASSWORD=secret

LOG_FILE=var/log/app.log
LOG_LEVEL=debug
```

Notes :

- `var/` doit exister et être accessible en écriture par le serveur web si vous loggez dans `var/log/`.
- Selon la manière dont vous chargez Dotenv, vous pouvez lire les variables via `getenv()` ou `$_ENV`.

## Démarrage local (sans Docker)

Ce framework est agnostique, mais pour tester rapidement vous pouvez utiliser le serveur PHP :

```bash
php -S 127.0.0.1:8000 -t public
```

Puis ouvrez :

- http://127.0.0.1:8000/

## Routage

Les routes sont déclarées dans `config/routes.php` (format compatible League\Route).

Exemple :

```php
return [
  ['GET', '/', \App\Controller\HomeController::class],
  ['GET', '/test/{id:\\d+}', \App\Controller\HomeController::class . '::test'],
];
```

## Dépendances (DI)

- Le container (PHP-DI) construit les objets et injecte les dépendances automatiquement.
- Exemple : un contrôleur reçoit un repository, le repository reçoit `BDDFactory`.

Exemple contrôleur :

```php
final class DemoController
{
    public function __construct(private DemoRepository $demoRepository) {}

    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $users = $this->demoRepository->findAll();
        // ...
    }
}
```

Exemple repository (base) :

```php
abstract class AbstractRepository
{
    public function __construct(protected BDDFactory $bddFactory) {}
}
```

## Base de données

- `BDDFactory` centralise la création de connexion (MyPDO).
- La connexion est obtenue uniquement quand nécessaire :

```php
$pdo = $this->bddFactory->getConnection();
```

## Logs (PSR-3)

Un logger PSR-3 (`Psr\Log\LoggerInterface`) est disponible via DI. Vous pouvez l’injecter dans n’importe quel service :

```php
public function __construct(private LoggerInterface $logger) {}
```

Pour capturer les warnings/notices PHP avec Monolog, enregistrer un handler global (au bootstrap ou via un middleware dédié) :

- `Monolog\ErrorHandler::register($logger);`

## Tests

Lancez les tests :

```bash
composer test
```

(ou)

```bash
vendor/bin/phpunit
```

## Qualité de code

Selon les outils présents :

```bash
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix
```

## Git

Ce dépôt versionne :

- code + config portable (`src/`, `public/`, `config/`, `tests/`, `composer.*`, docs)

Ce dépôt ignore :

- `.env`
- `vendor/`
- `var/` (runtime)
- logs/caches temporaires

## Licence

GPLv3 (voir fichier LICENSE).


