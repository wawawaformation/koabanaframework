<?php

declare(strict_types=1);

namespace App\Controller;

use GuzzleHttp\Psr7\Response;
use Koabana\Http\Session\FlashBag;
use Koabana\Http\Session\ProfileBag;
use Koabana\Http\Session\SessionBag;
use Koabana\View\PhpTemplateRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Classe abstraite de base pour tous les contrôleurs de l'application (MVC).
 *
 * Fournit des méthodes utilitaires pour produire des réponses HTTP :
 * - HTML via template,
 * - texte brut,
 * - JSON,
 * - redirection,
 * - erreurs HTTP (404, etc.),
 * - gestion des messages flash.
 *
 * Usage typique :
 *   class PageController extends AbstractController {
 *       public function home(ServerRequestInterface $request): ResponseInterface {
 *           return $this->render('pages/home', ['title' => 'Accueil']);
 *       }
 *   }
 *
 * Le contrôleur orchestre la logique applicative,
 * la vue est déléguée à PhpTemplateRenderer,
 * la réponse HTTP respecte PSR-7.
 *
 * @author david
 */
abstract class AbstractController
{
    /**
     * Moteur de rendu des templates PHP.
     */
    protected PhpTemplateRenderer $view;

    /**
     * Initialise le contrôleur avec le moteur de rendu.
     *
     * @param PhpTemplateRenderer $view Moteur de rendu des vues
     */
    public function __construct(PhpTemplateRenderer $view)
    {
        $this->view = $view;
    }

    /**
     * Rend un template de vue et retourne une réponse HTTP HTML.
     *
     * Utilise le moteur de rendu PHP pour générer le contenu HTML à partir d'un template,
     * de données et éventuellement d'un layout. Le résultat est encapsulé dans une
     * réponse PSR-7 avec le bon Content-Type et le code HTTP souhaité.
     *
     * @param ServerRequestInterface $request  Requête HTTP
     * @param string                 $template Nom du template à afficher (ex: 'pages/home')
     * @param array<string, mixed>   $data     Données à injecter dans la vue (clé => valeur)
     * @param int                    $status   Code de statut HTTP (200 par défaut)
     * @param null|string            $layout   Layout à utiliser (ex: 'layout/main'), null pour désactiver
     *
     * @return ResponseInterface Réponse HTTP contenant le HTML généré
     *
     * @example
     *   return $this->render('pages/mentions-legales', [
     *       'page' => $page,
     *   ]);
     */
    protected function render(
        ServerRequestInterface $request,
        string $template,
        array $data = [],
        int $status = 200,
        ?string $layout = 'layout/main',
    ): ResponseInterface {
        if (!\array_key_exists('flashes', $data)) {
            $data['flashes'] = $this->consumeFlashes($request);
        }
        $data['profile'] = $this->getUserProfile($request);
        $data['csrf_token'] = (string) $request->getAttribute('csrf_token', '');

        $html = $this->view->render($template, $data, $layout);

        return new Response(
            $status,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $html,
        );
    }

    /**
     * Retourne une réponse HTTP contenant du HTML brut.
     *
     * @param string                $html    Contenu HTML
     * @param int                   $status  Code de statut HTTP
     * @param array<string, string> $headers En-têtes HTTP personnalisés
     *
     * @return ResponseInterface Réponse HTTP
     */
    protected function html(
        string $html,
        int $status = 200,
        array $headers = [],
    ): ResponseInterface {
        $headers = $this->withDefaultHeader(
            $headers,
            'Content-Type',
            'text/html; charset=utf-8',
        );

        return new Response($status, $headers, $html);
    }

    /**
     * Retourne une réponse HTTP contenant du texte brut.
     *
     * @param string                $text    Contenu texte
     * @param int                   $status  Code de statut HTTP
     * @param array<string, string> $headers En-têtes HTTP personnalisés
     *
     * @return ResponseInterface Réponse HTTP
     */
    protected function text(
        string $text,
        int $status = 200,
        array $headers = [],
    ): ResponseInterface {
        $headers = $this->withDefaultHeader(
            $headers,
            'Content-Type',
            'text/plain; charset=utf-8',
        );

        return new Response($status, $headers, $text);
    }

    /**
     * Retourne une réponse HTTP JSON.
     *
     * Les données sont encodées en JSON avec des options sûres
     * (pas d'échappement Unicode ou des slashs).
     *
     * @param array<string, mixed>  $data    Données à encoder en JSON
     * @param int                   $status  Code de statut HTTP
     * @param array<string, string> $headers En-têtes HTTP personnalisés
     *
     * @return ResponseInterface Réponse HTTP JSON
     */
    protected function json(
        array $data,
        int $status = 200,
        array $headers = [],
    ): ResponseInterface {
        $headers = $this->withDefaultHeader(
            $headers,
            'Content-Type',
            'application/json; charset=utf-8',
        );

        $json = \json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        if (false === $json) {
            return new Response(
                500,
                ['Content-Type' => 'text/plain; charset=utf-8'],
                'JSON encoding error',
            );
        }

        return new Response($status, $headers, $json);
    }

    /**
     * Retourne une réponse HTTP de redirection.
     *
     * @param string                $location URL de destination
     * @param int                   $status   Code de statut HTTP (302 par défaut)
     * @param array<string, string> $headers  En-têtes HTTP supplémentaires
     *
     * @return ResponseInterface Réponse HTTP de redirection
     */
    protected function redirect(
        string $location,
        int $status = 302,
        array $headers = [],
    ): ResponseInterface {
        $headers['Location'] = $location;

        return new Response($status, $headers);
    }

    /**
     * Retourne une réponse HTTP 404.
     *
     * @param string $message Message affiché
     *
     * @return ResponseInterface Réponse HTTP 404
     */
    protected function notFound(string $message = 'Not Found'): ResponseInterface
    {
        return $this->text($message, 404);
    }

    /**
     * Récupère le FlashBag depuis la requête (attribut 'flash').
     *
     * @param ServerRequestInterface $request Requête HTTP
     *
     * @return FlashBag Bag de messages flash
     *
     * @throws \RuntimeException Si l'attribut 'flash' est absent ou invalide
     */
    protected function flash(ServerRequestInterface $request): FlashBag
    {
        $flash = $request->getAttribute('flash');

        if (!$flash instanceof FlashBag) {
            throw new \RuntimeException('FlashBag introuvable dans la requête (attribut "flash").');
        }

        return $flash;
    }

    /**
     * Ajoute un message flash à la requête (via FlashBag).
     *
     * @param ServerRequestInterface $request Requête HTTP
     * @param string                 $type    Type de message (info, success, error...)
     * @param string                 $message Message à stocker
     */
    protected function addFlash(ServerRequestInterface $request, string $type, string $message): void
    {
        $this->flash($request)->add($type, $message);
    }

    /**
     * Récupère et consomme tous les messages flash de la requête.
     *
     * Utilise le FlashBag stocké dans l'attribut 'flash' de la requête pour
     * obtenir tous les messages (par type), puis les supprime de la session.
     *
     * @param ServerRequestInterface $request Requête HTTP contenant le FlashBag
     *
     * @return array<string, list<string>> Tableau associatif type => liste de messages
     */
    protected function consumeFlashes(ServerRequestInterface $request): array
    {
        return $this->flash($request)->all();
    }

    /**
     * Récupère le ProfileBag depuis la requête (attribut 'profile').
     *
     * @param ServerRequestInterface $request Requête HTTP
     *
     * @return ProfileBag Bag de profil utilisateur
     *
     * @throws \RuntimeException Si l'attribut 'profile' est absent ou invalide
     */
    protected function profile(ServerRequestInterface $request): ProfileBag
    {
        $profile = $request->getAttribute('profile');

        if (!$profile instanceof ProfileBag) {
            throw new \RuntimeException('ProfileBag introuvable dans la requête (attribut "profile").');
        }

        return $profile;
    }

    /**
     * Récupère le SessionBag depuis la requête (attribut 'session').
     *
     * @param ServerRequestInterface $request Requête HTTP
     *
     * @return SessionBag Bag de session générique
     *
     * @throws \RuntimeException Si l'attribut 'session' est absent ou invalide
     */
    protected function session(ServerRequestInterface $request): SessionBag
    {
        $session = $request->getAttribute('session');

        if (!$session instanceof SessionBag) {
            throw new \RuntimeException('SessionBag introuvable dans la requête (attribut "session").');
        }

        return $session;
    }

    /**
     * Récupère l'état de connexion de l'utilisateur
     */
    protected function isLogged(ServerRequestInterface $request): bool
    {
        return $this->profile($request)->isLogged();
    }

    /**
     * Récupère le profil utilisateur sous forme de tableau
     *
     * @return array<string, mixed>
     */
    protected function getUserProfile(ServerRequestInterface $request): array
    {
        return $this->profile($request)->toArray();
    }

    /**
     * Vérifie si l'utilisateur est connecté, sinon redirige vers la page de connexion.
     */
    protected function requireLogin(ServerRequestInterface $request): ?ResponseInterface
    {
        if (!$this->profile($request)->isLogged()) {
            $this->addFlash($request, 'danger', 'Vous devez être connecté(e) pour accéder à cette page.');

            return $this->redirect('/auth/login');
        }

        return null;
    }

    /**
     * Ajoute un en-tête par défaut s'il n'existe pas déjà (insensible à la casse).
     *
     * @param array<string, string> $headers En-têtes existants
     * @param string                $name    Nom de l'en-tête
     * @param string                $value   Valeur de l'en-tête
     *
     * @return array<string, string> En-têtes HTTP finaux
     */
    private function withDefaultHeader(
        array $headers,
        string $name,
        string $value,
    ): array {
        foreach ($headers as $key => $_) {
            if (\strtolower($key) === \strtolower($name)) {
                return $headers;
            }
        }

        $headers[$name] = $value;

        return $headers;
    }
}
