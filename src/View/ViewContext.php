<?php

declare(strict_types=1);

namespace Koabana\View;

/**
 * Contexte de vue partagé entre les templates.
 *
 * Cette classe fournit un ensemble d'outils simples permettant :
 * - la gestion de sections nommées (header, content, footer, etc.),
 * - l'accumulation de feuilles de style,
 * - l'accumulation de scripts JavaScript (header / footer),
 * - la communication contrôlée entre templates et layout.
 *
 * Le fonctionnement repose volontairement sur :
 * - l'output buffering (ob_start / ob_get_clean),
 * - des conventions simples,
 * - l'absence de magie ou de moteur de template externe.
 */
final class ViewContext
{
    public const MENU_ACCUEIL = 'accueil';

    private const ALLOWED_MENUS = [
        self::MENU_ACCUEIL,
    ];

    /**
     * Sections rendues.
     *
     * @var array<string, string>
     */
    private array $sections = [];

    /**
     * Sections actuellement ouvertes.
     *
     * Utilisé pour éviter les ouvertures multiples
     * et garantir l'intégrité du rendu.
     *
     * @var array<string, bool>
     */
    private array $openSections = [];

    /**
     * Feuilles de style enregistrées.
     *
     * La clé correspond au href afin d'éviter les doublons.
     *
     * @var array<string, string>
     */
    private array $stylesheets = [];

    /**
     * Scripts JavaScript à inclure dans le header.
     *
     * @var array<string, string>
     */
    private array $headerJs = [];

    /**
     * Scripts JavaScript à inclure en fin de page.
     *
     * @var array<string, string>
     */
    private array $footerJs = [];

    /**
     * Indique si le modal de confirmation de suppression est activé
     */
    private bool $confirmDeleteModalEnabled = false;

    /**
     * Menu actif (pour la mise en évidence dans la navigation).
     */
    private ?string $activeMenu = null;

    /**
     * Démarre la capture d'une section.
     *
     * @param string $name Nom de la section
     *
     * @throws \RuntimeException Si le nom est vide ou si la section est déjà ouverte
     *
     * @var array<string, null|bool|float|int|string>
     */
    private array $profile = [];

    public function start(string $name): void
    {
        $name = \trim($name);
        if ('' === $name) {
            throw new \RuntimeException('Section name cannot be empty.');
        }

        if (isset($this->openSections[$name])) {
            throw new \RuntimeException('Section already started: '.$name);
        }

        $this->openSections[$name] = true;
        \ob_start();
    }

    /**
     * Termine la capture d'une section.
     *
     * @param string $name Nom de la section
     *
     * @throws \RuntimeException Si la section n'a pas été démarrée
     */
    public function end(string $name): void
    {
        $name = \trim($name);
        if ('' === $name) {
            throw new \RuntimeException('Section name cannot be empty.');
        }

        if (!isset($this->openSections[$name])) {
            throw new \RuntimeException('Section not started: '.$name);
        }

        unset($this->openSections[$name]);

        $this->sections[$name] = (string) \ob_get_clean();
    }

    /**
     * Retourne le contenu d'une section.
     *
     * @param string $name    Nom de la section
     * @param string $default Valeur par défaut si la section n'existe pas
     *
     * @return string Contenu de la section
     */
    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /**
     * Enregistre une feuille de style.
     *
     * @param string               $href       URL de la feuille de style
     * @param array<string, mixed> $attributes Attributs HTML supplémentaires
     */
    public function addStyleSheet(string $href, array $attributes = []): void
    {
        $href = \trim($href);
        if ('' === $href) {
            return;
        }

        $this->stylesheets[$href] = $this->buildLinkTag($href, $attributes);
    }

    /**
     * Retourne l'ensemble des balises <link> pour les stylesheets.
     *
     * @return string HTML généré
     */
    public function styleSheets(): string
    {
        return \implode("\n", $this->stylesheets);
    }

    /**
     * Enregistre un script JavaScript à inclure dans le header.
     *
     * @param string               $src        URL du script
     * @param array<string, mixed> $attributes Attributs HTML supplémentaires
     */
    public function addHeaderJs(string $src, array $attributes = []): void
    {
        $src = \trim($src);
        if ('' === $src) {
            return;
        }

        $this->headerJs[$src] = $this->buildScriptTag($src, $attributes);
    }

    /**
     * Retourne les scripts JavaScript à inclure dans le header.
     *
     * @return string HTML généré
     */
    public function headerJs(): string
    {
        return \implode("\n", $this->headerJs);
    }

    /**
     * Enregistre un script JavaScript à inclure en fin de page.
     *
     * @param string               $src        URL du script
     * @param array<string, mixed> $attributes Attributs HTML supplémentaires
     */
    public function addFooterJs(string $src, array $attributes = []): void
    {
        $src = \trim($src);
        if ('' === $src) {
            return;
        }

        $this->footerJs[$src] = $this->buildScriptTag($src, $attributes);
    }

    /**
     * Retourne les scripts JavaScript à inclure en fin de page.
     *
     * @return string HTML généré
     */
    public function footerJs(): string
    {
        return \implode("\n", $this->footerJs);
    }

    /**
     * Passe l'attribut confirmDeleteModalEnabled à True
     */
    public function enableConfirmDeleteModal(): void
    {
        $this->confirmDeleteModalEnabled = true;
    }

    /**
     * Indique si le modal de confirmation de suppression est activé
     */
    public function isConfirmDeleteModalEnabled(): bool
    {
        return $this->confirmDeleteModalEnabled;
    }

    /**
     * Retourne la clé du menu actif
     */
    public function setActiveMenu(string $key): void
    {
        if (!\in_array($key, self::ALLOWED_MENUS, true)) {
            throw new \InvalidArgumentException('Unknown menu key: '.$key);
        }

        $this->activeMenu = $key;
    }

    /**
     * Indique si un menu est actif
     */
    public function isActiveMenu(string $key): bool
    {
        return $this->activeMenu === $key;
    }

    /**
     * Réinitialise complètement le contexte de vue.
     *
     * Cette méthode est appelée avant chaque rendu afin d'éviter
     * toute fuite d'état entre deux requêtes.
     */
    public function reset(): void
    {
        $this->sections = [];
        $this->openSections = [];
        $this->stylesheets = [];
        $this->headerJs = [];
        $this->footerJs = [];
        $this->confirmDeleteModalEnabled = false;
    }

    /**
     * Ajoute les informations de profil
     *
     * @param array<string, null|bool|float|int|string> $profile Données de profil
     */
    public function addProfileInfo(array $profile): void
    {
        $this->profile = $profile;
    }

    /**
     * Retourne les informations de profil
     *
     * @return array<string, null|bool|float|int|string>
     */
    public function getProfileInfos(): array
    {
        return $this->profile;
    }

    public function getProfileAttribute(string $attribute): false|string
    {
        if (!\in_array($attribute, ['first_name', 'last_name', 'email'], true)) {
            return false;
        }

        return $this->profile[$attribute] ?? '';
    }

    /**
     * Échappe une chaîne pour une sortie HTML.
     */
    public function e(string $input): string
    {
        return \htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Construit une balise <link>.
     *
     * @param string               $href       URL de la feuille de style
     * @param array<string, mixed> $attributes Attributs HTML supplémentaires
     *
     * @return string Balise HTML
     */
    private function buildLinkTag(string $href, array $attributes): string
    {
        $attrs = \array_merge(
            ['rel' => 'stylesheet', 'href' => $href],
            $attributes,
        );

        return '<link'.$this->buildAttributes($attrs).'>';
    }

    /**
     * Construit une balise <script>.
     *
     * @param string               $src        URL du script
     * @param array<string, mixed> $attributes Attributs HTML supplémentaires
     *
     * @return string Balise HTML
     */
    private function buildScriptTag(string $src, array $attributes): string
    {
        $attrs = \array_merge(['src' => $src], $attributes);

        return '<script'.$this->buildAttributes($attrs).'></script>';
    }

    /**
     * Construit une chaîne d'attributs HTML.
     *
     * @param array<string, null|scalar> $attributes Attributs HTML
     *
     * @return string Chaîne d'attributs
     */
    private function buildAttributes(array $attributes): string
    {
        $parts = [];

        foreach ($attributes as $name => $value) {
            if (null === $value) {
                continue;
            }

            $name = \trim((string) $name);
            if ('' === $name) {
                continue;
            }

            /**
             * Important (pédagogique) :
             * Aucun échappement XSS automatique n'est appliqué ici.
             * La gestion de l'échappement est volontairement laissée
             * à la charge des templates / stagiaires.
             */
            $parts[] = \sprintf(' %s="%s"', $name, (string) $value);
        }

        return \implode('', $parts);
    }
}
