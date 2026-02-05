<?php

declare(strict_types=1);

namespace Koabana\View;

/**
 * Moteur de rendu de templates PHP natifs.
 *
 * Cette classe permet de rendre des vues PHP en utilisant :
 * - un système de templates simples basés sur `require`,
 * - un layout optionnel,
 * - un contexte de vue partagé (ViewContext).
 *
 * Objectifs :
 * - rester volontairement simple et lisible,
 * - éviter toute dépendance à un moteur de templates externe,
 *
 * Le rendu repose sur :
 * - ob_start() / ob_get_clean(),
 * - l'injection contrôlée de variables dans le scope du template,
 * - un gabarit (layout) optionnel enveloppant le contenu.
 */
final class PhpTemplateRenderer
{
    /**
     * Contexte de vue partagé entre les templates.
     */
    private ViewContext $view;

    /**
     * Initialise le moteur de rendu.
     *
     * @param string           $templatesPath Chemin racine des templates
     * @param null|ViewContext $view          Contexte de vue optionnel
     */
    public function __construct(
        private string $templatesPath,
        ?ViewContext $view = null,
    ) {
        $this->templatesPath = \rtrim($this->templatesPath, '/\\');
        $this->view = $view ?? new ViewContext();
    }

    /**
     * Rend un template avec des données et un layout optionnel.
     *
     * Le cycle de rendu est le suivant :
     * 1. Réinitialisation du contexte de vue.
     * 2. Rendu du template principal.
     * 3. Injection du contenu rendu dans le layout (si présent).
     *
     * @param string               $template Chemin du template (sans extension .php)
     * @param array<string, mixed> $data     Données métier accessibles dans la vue
     * @param null|string          $layout   Layout à utiliser ou null pour aucun layout
     *
     * @return string HTML généré
     */
    public function render(
        string $template,
        array $data = [],
        ?string $layout = 'layout/main',
    ): string {
        $this->view->reset();

        $content = $this->renderFile($template, $data);

        if (null === $layout) {
            return $content;
        }

        $data['content'] = $content;

        return $this->renderFile($layout, $data);
    }

    /**
     * Retourne le contexte de vue.
     *
     * Ce contexte est accessible dans tous les templates via la variable `$view`.
     */
    public function view(): ViewContext
    {
        return $this->view;
    }

    /**
     * Rend un fichier de template PHP.
     *
     * Cette méthode :
     * - vérifie l'existence du fichier,
     * - injecte les variables métier dans le scope local,
     * - expose le contexte de vue via `$view`,
     * - capture la sortie grâce à l'output buffering.
     *
     * @param string               $template Chemin du template (sans extension)
     * @param array<string, mixed> $data     Données métier
     *
     * @return string Contenu rendu
     *
     * @throws \RuntimeException Si le template est introuvable
     */
    private function renderFile(string $template, array $data): string
    {
        $template = \ltrim($template, '/\\');
        $file = $this->templatesPath.DIRECTORY_SEPARATOR.$template.'.php';

        if (!\is_file($file)) {
            throw new \RuntimeException('Template not found: '.$file);
        }

        // Variable disponible dans tous les templates : $view
        $view = $this->view;

        // Variables métier passées par le contrôleur
        \extract($data, EXTR_SKIP);

        \ob_start();

        require $file;

        return (string) \ob_get_clean();
    }
}
