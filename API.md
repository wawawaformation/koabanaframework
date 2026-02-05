# Koabana Framework - Documentation API

Framework PHP minimal et autonome basé sur **PSR-7/PSR-15** avec middleware, sessions, CSRF, forms, validation, logs et base de données.

## Table des matières

- [Installation](#installation)
- [Architecture](#architecture)
- [Routing](#routing)
- [Controllers](#controllers)
- [Models & Repositories](#models--repositories)
- [QueryBuilder](#querybuilder)
- [Forms & Validation](#forms--validation)
- [Sessions](#sessions)
- [Security](#security)
- [Views](#views)
- [Logging](#logging)
- [Exemples complets](#exemples-complets)

---

## Installation

### Prérequis
- **PHP 8.4+**
- **Composer**
- **PDO** (MySQL/SQLite)
- **mod_rewrite** (Apache) — pour le URL rewriting

### Setup
```bash
git clone https://github.com/user/koabana.git
cd koabana
cp .env.example .env  # Adapter les variables
composer install
```

### Serveur local
```bash
php -S 127.0.0.1:8000 -t public
```

Ouvrez http://127.0.0.1:8000

### Configuration serveur

#### Apache
Le `.htaccess` est déjà configuré pour router toutes les requêtes vers `index.php`.

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^ index.php [L]
```

Vérifiez que `mod_rewrite` est activé :
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
}
```

#### PHP Built-in Server (dev only)
```bash
php -S 127.0.0.1:8000 -t public
```

---

## Architecture

### Structure de projet
```
koabana/
├── public/
│   ├── index.php           # Front controller
│   └── css/
├── src/
│   ├── Bootstrap/          # Factories (DI)
│   ├── Controller/          # Contrôleurs
│   ├── Database/           # PDO + QueryBuilder
│   ├── Form/               # Form builder + Fields
│   ├── Http/               # Kernel + Middleware
│   ├── Log/                # LoggerFactory
│   ├── Model/
│   │   ├── Entity/         # Entités
│   │   └── Repository/     # Data access
│   └── View/               # Template renderer
├── config/
│   ├── routes.php          # Routes
│   ├── containers.php      # DI
│   └── middlewares.php     # Middleware order
├── views/                  # Templates PHP
├── tests/                  # Tests PHPUnit
└── var/                    # Logs, cache

```

### Middleware Stack (ordre d'exécution)
```
Request
  ↓
[1] ErrorHandlerMiddleware     → Catch exceptions, Whoops (dev)
  ↓
[2] SessionMiddleware          → Start session, inject Bags
  ↓
[3] CsrfMiddleware             → Validate tokens
  ↓
[4] TrailingSlashMiddleware    → Normalize URLs
  ↓
[5] SecurityHeadersMiddleware  → CSP, HSTS, X-Frame-Options
  ↓
[6] Router                     → Dispatch to controller
  ↓
Response
```

---

## Routing

### Déclaration de routes

Fichier : `config/routes.php`

```php
return [
    ['GET', '/', [HomeController::class, 'index']],
    ['GET', '/posts', [PostController::class, 'list']],
    ['GET', '/posts/{id:\d+}', [PostController::class, 'show']],
    ['POST', '/posts', [PostController::class, 'store']],
    ['PUT', '/posts/{id:\d+}', [PostController::class, 'update']],
    ['DELETE', '/posts/{id:\d+}', [PostController::class, 'delete']],
];
```

### Variables d'URL
```php
// Route: /posts/{id:\d+}
// URL:   /posts/123

// Dans le contrôleur:
public function show(ServerRequestInterface $request, array $args): ResponseInterface
{
    $id = (int) $args['id']; // 123
}
```

---

## Controllers

### Classe de base

Tous les contrôleurs héritent d'`AbstractController`.

```php
namespace Koabana\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PostController extends AbstractController
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->render($request, 'pages/posts', [
            'title' => 'Tous les posts',
        ]);
    }

    public function show(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $post = $this->getRepository()->findById($id);

        if (!$post) {
            return $this->notFound($request);
        }

        return $this->render($request, 'pages/post', [
            'post' => $post,
        ]);
    }

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        
        // Traiter les données
        
        $this->addFlash($request, 'success', 'Post créé !');
        return $this->redirect('/posts');
    }
}
```

### Méthodes disponibles

#### Rendu de templates
```php
// Rendre un template avec layout
$this->render($request, 'pages/home', [
    'title' => 'Accueil',
], 200, 'layout/main');

// Rendre sans layout
$this->render($request, 'pages/home', [], 200, null);
```

#### Réponses spéciales
```php
// HTML brut
$this->html('<h1>Hello</h1>');

// JSON
$this->json(['status' => 'ok', 'data' => $data]);

// Texte
$this->text('Succès');

// Redirection
$this->redirect('/path');

// Erreurs HTTP
$this->notFound($request);           // 404
$this->forbidden($request);           // 403
$this->badRequest($request);          // 400
$this->serverError($request);         // 500
```

#### Sessions
```php
$session = $this->session($request);  // SessionBag
$profile = $this->profile($request);  // ProfileBag
$flash = $this->flash($request);      // FlashBag
```

#### Messages flash
```php
$this->addFlash($request, 'success', 'Opération réussie !');
$this->addFlash($request, 'error', 'Une erreur s\'est produite.');
$this->addFlash($request, 'info', 'Information');
```

---

## Models & Repositories

### Entités

Héritent d'`AbstractEntity` (id, createdAt, updatedAt).

```php
namespace Koabana\Model\Entity;

final class Post extends AbstractEntity
{
    private string $title = '';
    private string $content = '';
    private bool $published = false;

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $value): void { $this->title = $value; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $value): void { $this->content = $value; }

    public function isPublished(): bool { return $this->published; }
    public function setPublished(bool $value): void { $this->published = $value; }
}
```

### Repositories

Héritent d'`AbstractRepository`.

```php
namespace Koabana\Model\Repository;

use Koabana\Database\BDDFactory;
use Koabana\Model\Entity\Post;

final class PostRepository extends AbstractRepository
{
    protected string $table = 'posts';
    protected string $entityClass = Post::class;

    // Hérité: findAll(), findById(), create(), update(), delete()
    
    // Méthodes custom
    public function findByStatus(string $status): array
    {
        return $this->query()
            ->where('status', '=', $status)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function findPublished(): array
    {
        return $this->query()
            ->where('published', '=', true)
            ->where('created_at', '<=', date('Y-m-d H:i:s'))
            ->orderBy('created_at', 'DESC')
            ->get();
    }
}
```

### CRUD complet

```php
$repo = new PostRepository($bddFactory);

// CREATE
$post = new Post();
$post->setTitle('Hello World');
$post->setContent('...');
$id = $repo->create($post); // Retourne l'ID

// READ
$post = $repo->findById(1);
$allPosts = $repo->findAll();

// UPDATE
$post->setTitle('Nouveau titre');
$repo->update($post);

// DELETE
$repo->delete(1);
// ou
$repo->deleteEntity($post);
```

---

## QueryBuilder

Mini query builder pour construire des requêtes SELECT complexes.

### Utilisation

```php
$repo = new PostRepository($bddFactory);

// SELECT simple
$posts = $repo->query()->get();

// WHERE simple
$published = $repo->query()
    ->where('published', true)
    ->get();

// Plusieurs WHERE (AND)
$recent = $repo->query()
    ->where('published', true)
    ->where('created_at', '>', '2026-01-01')
    ->get();

// WHERE IN
$featured = $repo->query()
    ->whereIn('category', ['tech', 'news'])
    ->get();

// WHERE BETWEEN
$thisMonth = $repo->query()
    ->whereBetween('created_at', '2026-02-01', '2026-02-28')
    ->get();

// ORDER BY
$recent = $repo->query()
    ->orderBy('created_at', 'DESC')
    ->get();

// LIMIT + OFFSET (pagination)
$page = $repo->query()
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->offset(20)
    ->get();

// COUNT
$count = $repo->query()
    ->where('published', true)
    ->count();

// FIRST (premier résultat ou null)
$post = $repo->query()
    ->where('slug', 'hello-world')
    ->first();

// Colonnes spécifiques
$titles = $repo->query()
    ->select(['id', 'title'])
    ->get();

// Debug: voir le SQL généré
echo $repo->query()
    ->where('published', true)
    ->toRawSql();
```

### Opérateurs supportés
- `=` — égal
- `!=` — pas égal
- `<` — inférieur
- `>` — supérieur
- `<=` — inférieur ou égal
- `>=` — supérieur ou égal
- `LIKE` — recherche textuelle
- `IN` — dans une liste (via `whereIn()`)
- `BETWEEN` — entre deux valeurs (via `whereBetween()`)

---

## Forms & Validation

### Créer un formulaire

```php
use Koabana\Form\Form;
use Koabana\Form\TextInput;
use Koabana\Form\EmailInput;
use Koabana\Form\Textarea;

$form = new Form('contact');

$form->add(new TextInput('name', [
    'required' => true,
    'minLength' => 2,
    'maxLength' => 100,
    'class' => 'form-control',
    'placeholder' => 'Votre nom',
]));

$form->add(new EmailInput('email', [
    'required' => true,
    'email' => true,
    'class' => 'form-control',
]));

$form->add(new Textarea('message', [
    'required' => true,
    'minLength' => 10,
    'maxLength' => 500,
    'class' => 'form-control',
    'rows' => 5,
]));
```

### Types de champs disponibles
- `TextInput` — Champ texte
- `EmailInput` — Champ email
- `PasswordInput` — Champ password (sans value)
- `Textarea` — Zone de texte
- `Select` — Liste déroulante
- `Checkbox` — Case à cocher

### Validation

```php
if ($method === 'POST') {
    $form->fill((array) $request->getParsedBody());
    
    if ($form->validate()) {
        $data = $form->getData();
        // Traiter les données validées
    } else {
        $errors = $form->errors(); // array<field => array<errors>>
    }
}
```

### Règles de validation

```php
new TextInput('name', [
    'required' => true,           // Champ obligatoire
    'minLength' => 2,             // Minimum 2 caractères
    'maxLength' => 100,           // Maximum 100 caractères
    'regex' => '/^[a-zA-Z ]+$/',  // Regex personnalisé
]);

new EmailInput('email', [
    'required' => true,
    'email' => true,              // Validation email
]);

new TextInput('age', [
    'min' => 18,                  // Valeur minimale
    'max' => 120,                 // Valeur maximale
]);
```

### Factory depuis Entity

```php
$form = Form::fromEntity('contact', $post, function(Form $form) {
    $form->add(new TextInput('title', ['required' => true]));
    $form->add(new Textarea('content', ['required' => true]));
});

// Le formulaire est bindé à l'entity
// Les champs sont hydratés depuis les getters
if ($form->validate()) {
    $post = $form->getEntity(); // Entity mise à jour
}
```

### Rendu en template

```php
<?= $form->open('/contact', 'POST') ?>
    <?php foreach ($form->getFields() as $field): ?>
        <div class="form-group">
            <label for="<?= $field->getName() ?>">
                <?= ucfirst($field->getName()) ?>
            </label>
            <?= $field->render() ?>
            
            <?php $errs = $form->errors($field->getName()); ?>
            <?php if (!empty($errs)): ?>
                <div class="errors">
                    <?php foreach ($errs as $err): ?>
                        <p><?= htmlspecialchars($err) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    
    <?= $form->csrf() ?>
    <button type="submit">Envoyer</button>
<?= $form->close() ?>
```

---

## Sessions

### SessionBag (données génériques)

```php
$session = $this->session($request);

// Set
$session->set('user_preferences', [
    'theme' => 'dark',
    'language' => 'fr',
]);

// Get
$prefs = $session->get('user_preferences', []);
$theme = $session->get('user_preferences.theme', 'light');

// Has
if ($session->has('user_preferences')) {
    // ...
}

// Remove
$session->remove('user_preferences');

// All
$allData = $session->all();

// Clear
$session->clear();
```

### FlashBag (messages jetables)

```php
$flash = $this->flash($request);

// Add
$flash->add('success', 'Opération réussie !');
$flash->add('error', 'Une erreur s\'est produite.');

// Get (récupère et efface)
$messages = $flash->all(); // array<type => array<messages>>

// Get une catégorie
$errors = $flash->get('error'); // array<messages>

// Has
if ($flash->has('success')) {
    // ...
}

// Clear
$flash->clear();
```

### ProfileBag (profil utilisateur)

```php
$profile = $this->profile($request);

// Set (connexion)
$profile->set([
    'user_id' => 1,
    'user_firstname' => 'Alice',
    'user_email' => 'alice@example.com',
]);

// Check
$profile->isLogged(); // bool

// Get
$id = $profile->getId();
$name = $profile->getFirstname();
$email = $profile->getEmail();

// Array
$array = $profile->toArray();

// Logout
$profile->clear();
```

---

## Security

### CSRF Protection

Automatique sur les formulaires. Le token est injecté par le middleware.

```php
// Dans le formulaire
<?= $form->csrf() ?>

// Ou manuellement
<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
```

Exemptions (config `.env`) :
```dotenv
CSRF_EXEMPTIONS=/api/*,/webhook/*
```

### Sessions

- **HttpOnly** — Pas d'accès JavaScript
- **SameSite=Lax** — CSRF léger
- **Secure** — HTTPS en prod

### Headers de sécurité

```php
// Content-Security-Policy
Content-Security-Policy: default-src 'self'; ...

// X-Frame-Options
X-Frame-Options: DENY

// HSTS (HTTPS en prod)
Strict-Transport-Security: max-age=31536000; includeSubDomains

// Referrer-Policy
Referrer-Policy: strict-origin-when-cross-origin
```

### Prepared Statements

Toutes les requêtes utilisent les prepared statements PDO.

```php
// Sûr contre injection SQL
$stmt = $repo->statement('SELECT * FROM users WHERE email = ?', [$email]);

// Aussi:
$repo->query()->where('email', '=', $email)->get();
```

---

## Views

### Template PHP

Fichier : `views/pages/home.php`

```php
<?php
/** @var \Koabana\View\ViewContext $view */
?>

<h1><?= htmlspecialchars($title) ?></h1>

<?php foreach ($posts as $post): ?>
    <article>
        <h2><?= htmlspecialchars($post['title']) ?></h2>
        <p><?= htmlspecialchars(substr($post['content'], 0, 200)) ?>...</p>
    </article>
<?php endforeach; ?>
```

### Layouts

Fichier : `views/layout/main.php`

```php
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title ?? 'Mon site') ?></title>
</head>
<body>
    <?php include 'views/partials/header.php'; ?>
    
    <main>
        <?= $content ?>
    </main>
    
    <?php include 'views/partials/footer.php'; ?>
</body>
</html>
```

### ViewContext

```php
// Dans les templates
$view->startSection('scripts');
// ...
$view->endSection();

$view->section('scripts'); // Récupère la section

$view->addStylesheet('/css/style.css');
$view->addStylesheet('/css/bootstrap.css', ['integrity' => '...']);

$view->addHeaderJs('/js/htmx.js');
$view->addFooterJs('/js/app.js');

$view->confirmDeleteModal('#btn-delete', 'Êtes-vous sûr ?');

$view->setActiveMenu('posts');
```

---

## Logging

### PSR-3 Monolog

```php
use Psr\Log\LoggerInterface;

final class PostController extends AbstractController
{
    public function __construct(
        PhpTemplateRenderer $view,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        // ...
        
        $this->logger->info('Post créé', [
            'post_id' => $post->getId(),
            'user_id' => $user->getId(),
        ]);
    }
}
```

### Niveaux

```php
$logger->emergency('Action urgente');
$logger->alert('Alerte');
$logger->critical('Critique');
$logger->error('Erreur');
$logger->warning('Avertissement');
$logger->notice('Notice');
$logger->info('Information');
$logger->debug('Debug');
```

---

## Exemples complets

### CRUD Posts complet

**Controller**
```php
namespace Koabana\Controller;

use Koabana\Form\Form;
use Koabana\Form\TextInput;
use Koabana\Form\Textarea;
use Koabana\Model\Repository\PostRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PostController extends AbstractController
{
    public function __construct(
        private PostRepository $repo,
    ) {}

    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $page = (int) ($request->getQueryParams()['page'] ?? 1);
        $offset = ($page - 1) * 10;
        
        $posts = $this->repo->query()
            ->where('published', true)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->offset($offset)
            ->get();
        
        return $this->render($request, 'posts/list', ['posts' => $posts]);
    }

    public function show(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $post = $this->repo->findById((int) $args['id']);
        
        if (!$post) {
            return $this->notFound($request);
        }
        
        return $this->render($request, 'posts/show', ['post' => $post]);
    }

    public function create(ServerRequestInterface $request): ResponseInterface
    {
        $form = Form::fromEntity('post', new Post(), function(Form $form) {
            $form->add(new TextInput('title', ['required' => true]));
            $form->add(new Textarea('content', ['required' => true]));
        });
        
        $form->setCsrfToken($request->getAttribute('csrf_token', ''));
        
        return $this->render($request, 'posts/form', ['form' => $form]);
    }

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $post = new Post();
        $form = Form::fromEntity('post', $post, function(Form $form) {
            $form->add(new TextInput('title', ['required' => true]));
            $form->add(new Textarea('content', ['required' => true]));
        });
        
        $form->fill((array) $request->getParsedBody());
        
        if ($form->validate()) {
            $post->setPublished(false);
            $this->repo->create($post);
            $this->addFlash($request, 'success', 'Post créé !');
            return $this->redirect('/posts');
        }
        
        return $this->render($request, 'posts/form', ['form' => $form]);
    }

    public function edit(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $post = $this->repo->findById((int) $args['id']);
        
        if (!$post) {
            return $this->notFound($request);
        }
        
        $form = Form::fromEntity('post', $post, function(Form $form) {
            $form->add(new TextInput('title', ['required' => true]));
            $form->add(new Textarea('content', ['required' => true]));
        });
        
        $form->setCsrfToken($request->getAttribute('csrf_token', ''));
        
        return $this->render($request, 'posts/form', ['form' => $form, 'post' => $post]);
    }

    public function update(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $post = $this->repo->findById((int) $args['id']);
        
        if (!$post) {
            return $this->notFound($request);
        }
        
        $form = Form::fromEntity('post', $post, function(Form $form) {
            $form->add(new TextInput('title', ['required' => true]));
            $form->add(new Textarea('content', ['required' => true]));
        });
        
        $form->fill((array) $request->getParsedBody());
        
        if ($form->validate()) {
            $this->repo->update($post);
            $this->addFlash($request, 'success', 'Post mis à jour !');
            return $this->redirect('/posts/' . $post->getId());
        }
        
        return $this->render($request, 'posts/form', ['form' => $form, 'post' => $post]);
    }

    public function delete(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $this->repo->delete((int) $args['id']);
        $this->addFlash($request, 'success', 'Post supprimé !');
        return $this->redirect('/posts');
    }
}
```

**Routes**
```php
return [
    ['GET', '/posts', [PostController::class, 'list']],
    ['GET', '/posts/create', [PostController::class, 'create']],
    ['POST', '/posts', [PostController::class, 'store']],
    ['GET', '/posts/{id:\d+}', [PostController::class, 'show']],
    ['GET', '/posts/{id:\d+}/edit', [PostController::class, 'edit']],
    ['PUT', '/posts/{id:\d+}', [PostController::class, 'update']],
    ['DELETE', '/posts/{id:\d+}', [PostController::class, 'delete']],
];
```

---

## Configuration

### .env

```dotenv
APP_ENV=dev
DB_DSN=mysql:host=localhost;dbname=koabana;charset=utf8mb4
DB_USER=root
DB_PASSWORD=password
LOG_FILE=var/log/app.log
LOG_LEVEL=debug
CSRF_EXEMPTIONS=/api/*,/webhook/*
```

### DI Container

Fichier : `config/containers.php`

```php
use DI\ContainerBuilder;
use Koabana\View\PhpTemplateRenderer;

$builder = new ContainerBuilder();

$builder->addDefinitions([
    PhpTemplateRenderer::class => function() {
        return new PhpTemplateRenderer(__DIR__ . '/../views');
    },
    // Autres définitions...
]);

return $builder->build();
```

---

## Tests & QA

```bash
composer test      # PHPUnit
composer stan      # PHPStan (niveau 6)
composer cs-fix    # PHP-CS-Fixer
```

---

## Licence

GPLv3 - Voir [LICENSE.md](LICENSE.md)
