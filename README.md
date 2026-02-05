# Koabana Framework

Framework PHP minimal et autonome basé sur **PSR-7/PSR-15** avec middleware, sessions, CSRF, logs et base de données.

**Objectif** : infrastructure réutilisable et maintenable pour projets web PHP modernes.

## Caractéristiques

- **Middleware PSR-15** (Relay) — ErrorHandler, SecurityHeaders, Sessions, CSRF, TrailingSlash, Router  
- **Gestion des sessions** — SessionBag, FlashBag, ProfileBag avec sécurité (httponly, samesite)  
- **Protection CSRF** — Tokens basés session, validation automatique (exemptable)  
- **En-têtes sécurité** — CSP, X-Frame-Options, HSTS (conditionnel), Referrer-Policy  
- **Routing** — League Route avec DI automatique  
- **Base de données** — PDO wrapper (MyPDO) avec hydration entités, casting de types  
- **Logs PSR-3** — Monolog avec Whoops (dev) et erreurs 500 propres (prod)  
- **Qualité** — PHPStan niveau 6, PHP-CS-Fixer, PHPUnit, PHPDoc Doxygen-compatible

## Démarrage rapide

### Prérequis
- **PHP 8.4+** (ou 8.0+)
- **Composer**

### Installation
```bash
git clone https://github.com/user/koabana.git koabana
cd koabana
cp .env.example .env  # Adapter les variables
composer install
```

### Serveur local
```bash
php -S 127.0.0.1:8000 -t public
```

Ouvrez http://127.0.0.1:8000

### Tests & QA
```bash
composer test      # PHPUnit
composer stan      # PHPStan (niveau 6)
composer cs-fix    # PHP-CS-Fixer
```

## Architecture

### Structure
```
public/               → Front controller (index.php)
src/
  Controller/         → Contrôleurs (hérités de AbstractController)
  Model/
    Entity/           → Entités (hydration automatique)
    Repository/       → Repositories (PDO + casting de types)
  Http/
    Kernel.php        → Orchestrateur middleware
    Middleware/       → Stack complète (security, error, sessions, CSRF…)
    Session/          → SessionBag, FlashBag, ProfileBag
config/
  routes.php          → Déclaration des routes
  containers.php      → Configuration DI (PHP-DI)
  middlewares.php     → Ordre d'exécution middleware
tests/                → Tests unitaires
var/log/              → Logs applicatifs (à créer)
```

### Middleware Stack (ordre d'exécution)
```
Request
  ↓
[1] ErrorHandlerMiddleware     → Catch exceptions, Whoops (dev), logs
  ↓
[2] SessionMiddleware          → Démarrage session, injection Bags
  ↓
[3] CsrfMiddleware             → Validation tokens CSRF
  ↓
[4] TrailingSlashMiddleware    → Normalisation slashes (308 redirect)
  ↓
[5] SecurityHeadersMiddleware  → CSP, HSTS (HTTPS+prod), X-Frame, etc.
  ↓
[6] Router                     → Dispatch vers contrôleur
  ↓
Response
```

## Utilisation courante

### Contrôleur avec sessions
```php
<?php declare(strict_types=1);

namespace Koabana\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MyController extends AbstractController
{
    public function action(ServerRequestInterface $request): ResponseInterface
    {
        // Sessions
        $session = $this->session($request);  // SessionBag
        $profile = $this->profile($request);  // ProfileBag
        $flash = $this->flash($request);      // FlashBag
        
        // Stocker une donnée
        $session->set('cart', ['item_1' => 2]);
        
        // Ajouter message flash
        $this->addFlash($request, 'success', 'Votre commande est validée !');
        
        // Vérifier connexion utilisateur
        if (!$profile->isLogged()) {
            return $this->redirect('/login');
        }
        
        // Rendre template
        return $this->render($request, 'pages/my-page', [
            'user_id' => $profile->getId(),
        ]);
    }
}
```

### Routes
```php
<?php declare(strict_types=1);

return [
    ['GET', '/', [\Koabana\Controller\HomeController::class, 'index']],
    ['GET', '/product/{id:\\d+}', [\Koabana\Controller\ProductController::class, 'show']],
    ['POST', '/cart/add', [\Koabana\Controller\CartController::class, 'add']],
];
```

### Repository avec hydration
```php
<?php declare(strict_types=1);

namespace Koabana\Model\Repository;

use Koabana\Model\Entity\Product;

final class ProductRepository extends AbstractRepository
{
    public function findById(int $id): ?Product
    {
        $stmt = $this->statement(
            'SELECT * FROM products WHERE id = ?',
            [$id]
        );
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row, Product::class) : null;
    }
}
```

### Entité
```php
<?php declare(strict_types=1);

namespace Koabana\Model\Entity;

final class Product extends AbstractEntity
{
    private string $name = '';
    private float $price = 0.0;
    
    public function getName(): string { return $this->name; }
    public function setName(string $value): void { $this->name = $value; }
    
    public function getPrice(): float { return $this->price; }
    public function setPrice(float $value): void { $this->price = $value; }
}
```

## Configuration

### .env (non versionné, basé sur .env.example)
```dotenv
APP_ENV=dev
DB_DSN=mysql:host=localhost;dbname=app;charset=utf8mb4
DB_USER=app
DB_PASSWORD=your_secure_password
LOG_FILE=var/log/app.log
CSRF_EXEMPTIONS=/api/*,/webhook/*
```

### Variables clés
| Variable | Défaut | Utilité |
|----------|--------|---------|
| `APP_ENV` | `dev` | `dev` = Whoops actif, HSTS désactivé; `prod` = erreurs génériques |
| `DB_DSN` | — | PDO DSN (MySQL, SQLite, etc.) |
| `LOG_LEVEL` | `debug` | Niveau Monolog (debug, info, warning, error) |

## Sécurité

### Sessions
- HttpOnly (pas d'accès JS)
- SameSite=Lax (CSRF léger)
- Secure=conditionnel (HTTPS en prod)

### CSRF
- Token généré par session (64 chars hexadécimales)
- Validation sur POST/PUT/PATCH/DELETE
- Exemptions paramétrables (`/api/*`, `/webhook/*`)

### Headers HTTP
- CSP (Content-Security-Policy)
- X-Frame-Options: DENY
- HSTS (strict-transport-security) — HTTPS+prod uniquement
- Referrer-Policy: strict-origin-when-cross-origin

## API Sessions (Bags)

### SessionBag (données session génériques)
```php
$session->set('key', ['data' => 'value']);
$data = $session->get('key', []);
$session->has('key');
$session->remove('key');
$session->all();
$session->clear();
```

### FlashBag (messages jetables)
```php
$flash->add('success', 'Opération réussie !');
$flash->add('error', 'Une erreur s\'est produite.');
$messages = $flash->all();  // Récupère et efface
$flash->get('success');     // Une seule catégorie
$flash->has('error');
```

### ProfileBag (profil utilisateur)
```php
$profile->isLogged();
$profile->getId();
$profile->getFirstname();
$profile->getEmail();
$profile->set(['user_id' => 1, 'user_firstname' => 'Alice']);
$profile->clear();
$profile->toArray();
```

## Tests & Démo

Visitez `/demo/tests` pour explorer SessionBag, ProfileBag, FlashBag.

Routes de test :
- `GET /demo` — Page principale
- `GET /demo/form` — Formulaire CSRF demo
- `POST /demo/submit` — Traitement CSRF
- `GET /demo/tests` — Tests interactifs Bags
- `GET /demo/session/set` — Définir données session
- `GET /demo/session/get` — Récupérer données
- `GET /demo/profile/set` — Définir profil utilisateur
- `GET /demo/profile/get` — Récupérer profil
- `GET /demo/flash/add` — Ajouter message flash

## Prochaines étapes

- **AbstractRepository** → Implémentation CRUD complet
- **Système de formulaire** → Validation, binding, rendu HTML

## Support & Contributions

Pour des questions, ouvrez une issue. Les contributions sont bienvenues !

## Licence

GPLv3 (voir [LICENSE.md](LICENSE.md))


