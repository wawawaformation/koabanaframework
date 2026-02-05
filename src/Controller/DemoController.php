<?php

declare(strict_types=1);

namespace Koabana\Controller;

use GuzzleHttp\Psr7\Response;
use Koabana\Form\EmailInput;
use Koabana\Form\Form;
use Koabana\Form\TextInput;
use Koabana\Form\Textarea;
use Koabana\Http\Session\FlashBag;
use Koabana\Model\Repository\DemoRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Contrôleur de démonstration (page simple + formulaire CSRF).
 */
final class DemoController
{
    /**
     * @param DemoRepository  $demoRepository
     * @param LoggerInterface $logger
     */
    public function __construct(private DemoRepository $demoRepository, private LoggerInterface $logger) {}

    /**
     * @param array<string, string> $args
     *
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $html = '<h1>Koabana Framework</h1>';
        $html .= '<p>Framework PHP minimal et autonome basé sur PSR-7/PSR-15.</p>';

        $users = $this->demoRepository->findAllUsers();

        $this->logger->info('Fetching users from database');

        $html .= '<h2>Routes de démo</h2>';
        $html .= '<ul>';
        $html .= '<li><a href="/">Accueil</a></li>';
        $html .= '<li><a href="/demo">Démo (cette page)</a></li>';
        $html .= '<li><a href="/demo/form">Formulaire CSRF</a></li>';
        $html .= '<li><a href="/demo/form-demo">Formulaire complet (validation)</a></li>';
        $html .= '<li><a href="/demo/tests">Tests Bags (Sessions, Flash, Profile)</a></li>';
        $html .= '<li><a href="/demo/session/set">Gestion sessions</a></li>';
        $html .= '<li><a href="/demo/profile/login">Gestion profil</a></li>';
        $html .= '<li><a href="/demo/flash/add">Messages flash</a></li>';
        $html .= '</ul>';

        $html .= '<h2>Utilisateurs de la BDD</h2>';
        $html .= '<ul>';
        foreach ($users as $user) {
            $name = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
            $html .= '<li>' . $name . '</li>';
        }
        $html .= '</ul>';

        $html .= '<style>body { font-family: sans-serif; margin: 2rem; } h1 { color: #1976d2; } a { color: #1976d2; text-decoration: none; } a:hover { text-decoration: underline; } li { margin: 0.5rem 0; }</style>';

        return new Response(
            200,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $html,
        );
    }

    /**
     * Affiche un formulaire avec protection CSRF
     *
     * @param array<string, string> $args
     *
     * @return ResponseInterface
     */
    public function form(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $csrfToken = $request->getAttribute('csrf_token', '');

        /** @var null|FlashBag $flash */
        $flash = $request->getAttribute('flash');
        $messages = $flash?->all() ?? [];

        $flashHtml = '';
        foreach ($messages as $type => $msgs) {
            foreach ($msgs as $msg) {
                $msg = htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $flashHtml .= "<p style='color:".('success' === $type ? 'green' : 'red')."'>{$msg}</p>";
            }
        }

        $html = '<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Démo CSRF</title>
</head>
<body>
    <h1>Exemple de formulaire avec protection CSRF</h1>
    '.$flashHtml.'
    <form method="POST" action="/demo/submit">
        <input type="hidden" name="_csrf_token" value="'.htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8').'">
        
        <label for="name">Nom :</label>
        <input type="text" id="name" name="name" required>
        <br><br>
        
        <label for="email">Email :</label>
        <input type="email" id="email" name="email" required>
        <br><br>
        
        <button type="submit">Envoyer</button>
    </form>
</body>
</html>';

        return new Response(
            200,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $html,
        );
    }

    /**
     * Traite la soumission du formulaire
     *
     * @param array<string, string> $args
     *
     * @return ResponseInterface
     */
    public function submit(ServerRequestInterface $request, array $args): ResponseInterface
    {
        /** @var null|FlashBag $flash */
        $flash = $request->getAttribute('flash');

        $parsedBody = $request->getParsedBody();

        if (!is_array($parsedBody)) {
            $flash?->add('error', 'Données invalides');

            return new Response(302, ['Location' => '/demo/form']);
        }

        $name = htmlspecialchars((string) ($parsedBody['name'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $email = htmlspecialchars((string) ($parsedBody['email'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $this->logger->info('Formulaire soumis', ['name' => $name, 'email' => $email]);

        $flash?->add('success', "Merci {$name}, votre email {$email} a été enregistré !");

        return new Response(302, ['Location' => '/demo/form']);
    }

    /**
     * Affiche les tests d'utilisation des Bags (Session, Profile, Flash)
     *
     * @return ResponseInterface
     */
    public function testBags(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $profile = $request->getAttribute('profile');
        $flash = $request->getAttribute('flash');

        $html = '<h1>Tests des Bags</h1>';

        if ($session) {
            $html .= '<h2>SessionBag</h2>';
            $html .= '<p><strong>Type:</strong> '.get_class($session).'</p>';
            $html .= '<p><strong>Méthodes disponibles:</strong> get(), set(), has(), remove(), all(), clear()</p>';
            $html .= '<a href="/demo/session/set">Ajouter une donnée en session</a> | ';
            $html .= '<a href="/demo/session/view">Voir les données</a><br><br>';
        }

        if ($profile) {
            $html .= '<h2>ProfileBag</h2>';
            $html .= '<p><strong>Type:</strong> '.get_class($profile).'</p>';
            $html .= '<p><strong>État actuel:</strong></p>';
            $html .= '<ul>';
            $html .= '<li>Connecté: '.($profile->isLogged() ? 'Oui' : 'Non').'</li>';
            $html .= '<li>ID: '.($profile->getId() ?? 'N/A').'</li>';
            $html .= '<li>Prénom: '.($profile->getFirstname() ?? 'N/A').'</li>';
            $html .= '<li>Email: '.($profile->getEmail() ?? 'N/A').'</li>';
            $html .= '</ul>';
            $html .= '<a href="/demo/profile/login">Simuler une connexion</a> | ';
            $html .= '<a href="/demo/profile/logout">Simuler une déconnexion</a><br><br>';
        }

        if ($flash) {
            $html .= '<h2>FlashBag</h2>';
            $html .= '<p><strong>Type:</strong> '.get_class($flash).'</p>';
            $html .= '<p><strong>Messages actuels:</strong></p>';
            $messages = $flash->all();
            if (empty($messages)) {
                $html .= '<p><em>Aucun message flash</em></p>';
            } else {
                $html .= '<ul>';
                foreach ($messages as $type => $msgs) {
                    foreach ($msgs as $msg) {
                        $html .= '<li>['.htmlspecialchars($type).'] '.htmlspecialchars($msg).'</li>';
                    }
                }
                $html .= '</ul>';
            }
            $html .= '<a href="/demo/flash/add">Ajouter un message flash</a><br><br>';
        }

        $html .= '<a href="/demo">Retour</a>';

        return new Response(
            200,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $html,
        );
    }

    /**
     * Ajoute une donnée en session
     *
     * @return ResponseInterface
     */
    public function sessionSet(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $flash = $request->getAttribute('flash');

        if ($session) {
            $session->set('user_data', [
                'name' => 'Jean Dupont',
                'preferences' => ['theme' => 'dark', 'language' => 'fr'],
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
            $this->logger->info('Données ajoutées en session');
            $flash?->add('success', 'Données sauvegardées en session !');
        }

        return new Response(302, ['Location' => '/demo/session/view']);
    }

    /**
     * Affiche les données de session
     *
     * @return ResponseInterface
     */
    public function sessionView(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $flash = $request->getAttribute('flash');

        $html = '<h1>Données de session</h1>';

        $messages = $flash?->all() ?? [];
        foreach ($messages as $type => $msgs) {
            foreach ($msgs as $msg) {
                $msg = htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $color = 'success' === $type ? 'green' : ('error' === $type ? 'red' : 'blue');
                $html .= "<p style='color:{$color}'>{$msg}</p>";
            }
        }

        if ($session) {
            $data = $session->get('user_data', null);
            if ($data) {
                $html .= '<h2>user_data:</h2>';
                $html .= '<pre>'.htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)).'</pre>';
            } else {
                $html .= '<p><em>Aucune donnée "user_data" en session</em></p>';
            }
        }

        $html .= '<a href="/demo/session/set">Ajouter une donnée</a> | <a href="/demo/tests">Retour aux tests</a>';

        return new Response(
            200,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $html,
        );
    }

    /**
     * Simule une connexion utilisateur
     *
     * @return ResponseInterface
     */
    public function profileLogin(ServerRequestInterface $request): ResponseInterface
    {
        $profile = $request->getAttribute('profile');
        $flash = $request->getAttribute('flash');

        if ($profile) {
            $profile->set([
                'user_id' => 123,
                'user_firstname' => 'Alice',
                'user_email' => 'alice@example.com',
            ]);
            $this->logger->info('Utilisateur simulé connecté', ['id' => 123, 'name' => 'Alice']);
            $flash?->add('success', 'Alice (ID: 123) s\'est connectée !');
        }

        return new Response(302, ['Location' => '/demo/tests']);
    }

    /**
     * Simule une déconnexion utilisateur
     *
     * @return ResponseInterface
     */
    public function profileLogout(ServerRequestInterface $request): ResponseInterface
    {
        $profile = $request->getAttribute('profile');
        $flash = $request->getAttribute('flash');

        if ($profile) {
            $userId = $profile->getId();
            $profile->clear();
            $this->logger->info('Utilisateur déconnecté', ['id' => $userId]);
            $flash?->add('success', 'Utilisateur déconnecté !');
        }

        return new Response(302, ['Location' => '/demo/tests']);
    }

    /**
     * Ajoute un message flash
     *
     * @return ResponseInterface
     */
    public function flashAdd(ServerRequestInterface $request): ResponseInterface
    {
        $flash = $request->getAttribute('flash');

        if ($flash) {
            $flash->add('info', '📢 Message d\'information');
            $flash->add('success', '✅ Succès !');
            $flash->add('error', '❌ Erreur détectée');
            $this->logger->info('Messages flash ajoutés');
        }

        return new Response(302, ['Location' => '/demo/tests']);
    }

    /**
     * Démontre l'utilisation du formulaire
     *
     * @return ResponseInterface
     */
    public function formDemo(ServerRequestInterface $request): ResponseInterface
    {
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
            'placeholder' => 'Votre email',
        ]));

        $form->add(new Textarea('message', [
            'required' => true,
            'minLength' => 10,
            'maxLength' => 500,
            'class' => 'form-control',
            'rows' => 5,
            'placeholder' => 'Votre message',
        ]));

        // Récupère le token CSRF
        $csrfToken = $request->getAttribute('csrf_token', '');
        $form->setCsrfToken($csrfToken);

        // Hydrate depuis POST si présent
        $method = $request->getMethod();
        $errors = [];

        if ($method === 'POST') {
            $data = (array)$request->getParsedBody();
            $form->fill($data);

            if ($form->validate()) {
                $this->logger->info('Formulaire valide', $form->getData());
                return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], 
                    '<h1>✅ Formulaire reçu avec succès !</h1><pre>' . 
                    htmlspecialchars(json_encode($form->getData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') .
                    '</pre><a href="/demo/form-demo">Retour au formulaire</a>');
            }
            
            $errors = $form->errors();
            $this->logger->warning('Erreurs de validation', $errors);
        }

        // Génère le HTML du formulaire
        $html = '<h1>Formulaire de démonstration</h1>';
        $html .= $form->open('/demo/form-demo', 'POST', ['class' => 'form-demo']);

        foreach ($form->getFields() as $fieldName => $field) {
            $html .= '<div class="form-group">';
            $html .= '<label for="' . $fieldName . '">' . ucfirst($fieldName) . '</label>';
            $html .= $field->render();
            
            if (isset($errors[$fieldName])) {
                $html .= '<div class="errors">';
                foreach ($errors[$fieldName] as $error) {
                    $html .= '<p class="error">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= $form->csrf();
        $html .= '<button type="submit" class="btn">Envoyer</button>';
        $html .= $form->close();

        $html .= '<style>
            .form-demo { max-width: 500px; }
            .form-group { margin: 1rem 0; }
            label { display: block; font-weight: bold; margin-bottom: 0.5rem; }
            input, textarea, select { width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; }
            textarea { resize: vertical; }
            .errors { color: #d32f2f; margin-top: 0.5rem; }
            .error { margin: 0.25rem 0; font-size: 0.9rem; }
            .btn { background: #1976d2; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; margin-top: 1rem; }
            .btn:hover { background: #1565c0; }
        </style>';

        return new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], $html);
    }
}
