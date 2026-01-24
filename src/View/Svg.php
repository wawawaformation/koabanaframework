<?php

declare(strict_types=1);

namespace Koabana\View;

/**
 * Class Svg
 *
 * Permet de charger un fichier SVG et de modifier certains attributs avant rendu.
 *
 * Objectif :
 * - Normaliser le SVG (ex : suppression width/height si viewBox présent)
 * - Contrôler les attributs de la balise <svg> : class, role, aria-*, fill
 * - Rendre un SVG inline prêt à être injecté dans une vue
 *
 * Note :
 * Cette classe ne “sanitize” pas le contenu (scripts, foreignObject, etc.).
 * Elle est pensée pour des SVG de confiance (issus du projet).
 */
class Svg
{
    /**
     * Code source du SVG (balise <svg>…</svg> uniquement).
     */
    private string $svgContent;

    /**
     * Classes CSS à appliquer au SVG.
     *
     * @var list<string>
     */
    private array $class = [];

    /**
     * Rôle du SVG (ex: "img").
     */
    private ?string $role = null;

    /**
     * Attributs aria-* à appliquer (clé => valeur), clés stockées sous forme "aria-xxx".
     *
     * @var array<string, string>
     */
    private array $ariaAttributes = [];

    /**
     * Valeur de l'attribut fill (uniquement sur <svg>).
     */
    private ?string $fill = null;

    /**
     * Titre du SVG
     */
    private ?string $title = null;

    /**
     * ID du titre (pour aria-labelledby)
     */
    private ?string $titleId = null;

    /**
     * Charge un fichier SVG depuis le disque, vérifie sa validité XML, récupère le nœud <svg>
     * et normalise certains attributs.
     *
     * @param string $path Chemin vers le fichier SVG
     *
     * @throws \InvalidArgumentException si le fichier n'existe pas, n'est pas lisible, est vide,
     *                                   ou n'est pas un SVG valide
     */
    public function __construct(string $path)
    {
        $svg = $this->readFile($path);

        // Chargement XML robuste (évite les warnings + bloque le réseau)
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        $ok = $doc->loadXML($svg, LIBXML_NONET);
        if (!$ok) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            $msg = $errors ? trim($errors[0]->message) : 'XML invalide';

            throw new \InvalidArgumentException("Le fichier fourni n'est pas un SVG valide : {$path} ({$msg})");
        }

        libxml_use_internal_errors($previous);

        /** @var null|\DOMElement $svgNode */
        $svgNode = $doc->getElementsByTagName('svg')->item(0);
        if (!$svgNode) {
            throw new \InvalidArgumentException('Le fichier fourni ne contient pas de balise <svg> : '.$path);
        }

        // Classes existantes
        $existingClass = trim($svgNode->getAttribute('class'));
        if ('' !== $existingClass) {
            $classes = preg_split('/\s+/', $existingClass) ?: [];
            $classes = array_values(array_unique(array_filter($classes, static fn ($c) => '' !== $c)));
            $this->class = $classes;
        }

        // Role existant
        $existingRole = trim($svgNode->getAttribute('role'));
        $this->role = ('' !== $existingRole) ? $existingRole : null;

        // Aria existants
        foreach ($svgNode->attributes as $attr) {
            if (str_starts_with($attr->name, 'aria-')) {
                $this->ariaAttributes[$attr->name] = $attr->value;
            }
        }

        // Fill existant (uniquement si présent sur <svg>)
        $existingFill = trim($svgNode->getAttribute('fill'));
        $this->fill = ('' !== $existingFill) ? $existingFill : null;

        // Normalisation taille : on retire width/height seulement si viewBox existe
        if ($svgNode->hasAttribute('viewBox')) {
            $this->setAttribute($svgNode, 'width', null);
            $this->setAttribute($svgNode, 'height', null);
        }

        // On stocke uniquement le <svg> racine
        $this->svgContent = $doc->saveXML($svgNode) ?: '';
    }

    /**
     * Vide les attributs gérés par cette classe (classes, role, aria, fill).
     * Ne modifie pas le contenu interne (paths, groups, etc.).
     */
    public function clear(): self
    {
        $this->class = [];
        $this->role = null;
        $this->ariaAttributes = [];
        $this->fill = null;

        return $this;
    }

    /**
     * Rendu final du SVG inline, en réappliquant uniquement les attributs
     * configurés dans l'objet (pas de fusion avec l'original).
     *
     * Stratégie :
     * - Supprime du SVG source : class, role, fill, tous les aria-*
     * - Applique : les nouvelles classes, role, aria-*, fill
     *
     * @return string SVG (balise <svg>…</svg>)
     */
    public function render(): string
    {
        if ('' === trim($this->svgContent)) {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;

        $ok = $doc->loadXML($this->svgContent, LIBXML_NONET);
        if (!$ok) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            // Fallback : on renvoie le contenu brut si jamais ça casse
            return $this->svgContent;
        }

        libxml_use_internal_errors($previous);

        /** @var null|\DOMElement $svgNode */
        $svgNode = $doc->getElementsByTagName('svg')->item(0);
        if (!$svgNode) {
            return $this->svgContent;
        }

        // 1) Nettoyage total des attributs gérés (pas de fusion)
        $this->setAttribute($svgNode, 'class', null);
        $this->setAttribute($svgNode, 'role', null);
        $this->setAttribute($svgNode, 'fill', null);

        $toRemove = [];
        foreach ($svgNode->attributes as $attr) {
            if (str_starts_with($attr->name, 'aria-')) {
                $toRemove[] = $attr->name;
            }
        }
        foreach ($toRemove as $name) {
            $this->setAttribute($svgNode, $name, null);
        }

        // 2) Application des nouveaux attributs

        // class=""
        if ([] !== $this->class) {
            $classes = array_values(array_unique(array_filter($this->class, static fn ($c) => '' !== trim($c))));
            $this->setAttribute($svgNode, 'class', implode(' ', $classes));
        }

        // role=""
        if (null !== $this->role && '' !== trim($this->role)) {
            $this->setAttribute($svgNode, 'role', $this->role);
        }

        // aria-*
        foreach ($this->ariaAttributes as $name => $value) {
            $this->setAttribute($svgNode, $name, $value);
        }

        // fill=""
        if (null !== $this->fill && '' !== trim($this->fill)) {
            $this->setAttribute($svgNode, 'fill', $this->fill);
        }

        // --- Nettoyage title + aria-labelledby (pas de fusion) ---

        // Supprime tous les <title> enfants directs
        $titleNodes = [];
        foreach ($svgNode->childNodes as $child) {
            if ($child instanceof \DOMElement && 'title' === $child->tagName) {
                $titleNodes[] = $child;
            }
        }
        foreach ($titleNodes as $node) {
            $svgNode->removeChild($node);
        }

        // Supprime aria-labelledby (géré par la classe si on met un title)
        $this->setAttribute($svgNode, 'aria-labelledby', null);

        // --- Application d'un nouveau <title> si défini ---
        if (null !== $this->title) {
            $titleId = $this->titleId;
            if (null === $titleId || '' === $titleId) {
                // id stable à l’échelle du rendu (tu peux faire mieux si tu veux)
                $titleId = 'svg-title-'.substr(sha1($this->title), 0, 10);
            }

            $titleElt = $doc->createElement('title');
            $titleElt->setAttribute('id', $titleId);
            $titleElt->appendChild($doc->createTextNode($this->title));

            // On met <title> en premier enfant (bonne pratique)
            $first = $svgNode->firstChild;
            if ($first) {
                $svgNode->insertBefore($titleElt, $first);
            } else {
                $svgNode->appendChild($titleElt);
            }

            // Lie le svg au title
            $this->setAttribute($svgNode, 'aria-labelledby', $titleId);
        }

        return $doc->saveXML($svgNode) ?: $this->svgContent;
    }

    /**
     * Ajoute une classe CSS au SVG (sans doublon).
     *
     * @param string $class Classe CSS à ajouter (ex: "icon", "icon-lg")
     */
    public function addClass(string $class): self
    {
        $class = trim($class);

        if ('' !== $class && !in_array($class, $this->class, true)) {
            $this->class[] = $class;
        }

        return $this;
    }

    /**
     * Supprime une classe CSS du SVG.
     *
     * @param string $class Classe CSS à supprimer
     */
    public function removeClass(string $class): self
    {
        $class = trim($class);

        $this->class = array_values(array_filter(
            $this->class,
            static fn ($c) => $c !== $class,
        ));

        return $this;
    }

    /**
     * Vide la liste des classes CSS.
     */
    public function clearClasses(): self
    {
        $this->class = [];

        return $this;
    }

    /**
     * Définit un attribut aria-*.
     *
     * Remarque :
     * - $name peut être fourni avec ou sans préfixe "aria-"
     * - la clé est stockée sous forme "aria-xxx"
     *
     * @param string $name  Nom de l'attribut (ex: "hidden" ou "aria-hidden")
     * @param string $value Valeur (ex: "true", "false", "Fermer")
     */
    public function setAriaAttribute(string $name, string $value): self
    {
        $attrName = str_starts_with($name, 'aria-') ? $name : 'aria-'.$name;
        $this->ariaAttributes[$attrName] = trim($value);

        return $this;
    }

    /**
     * Supprime un attribut aria-*.
     *
     * @param string $name Nom de l'attribut (ex: "hidden" ou "aria-hidden")
     */
    public function removeAriaAttribute(string $name): self
    {
        $attrName = str_starts_with($name, 'aria-') ? $name : 'aria-'.$name;

        if (array_key_exists($attrName, $this->ariaAttributes)) {
            unset($this->ariaAttributes[$attrName]);
        }

        return $this;
    }

    /**
     * Vide la liste des attributs aria-*.
     */
    public function clearAriaAttributes(): self
    {
        $this->ariaAttributes = [];

        return $this;
    }

    /**
     * Définit l'attribut fill du SVG (uniquement sur <svg>).
     *
     * @param string $fill Valeur CSS (ex: "red", "#ff0000", "currentColor")
     */
    public function setFill(string $fill): self
    {
        $this->fill = trim($fill);

        return $this;
    }

    /**
     * Supprime l'attribut fill.
     */
    public function removeFill(): self
    {
        $this->fill = null;

        return $this;
    }

    /**
     * Définit le rôle du SVG.
     *
     * @param null|string $role Rôle (ex: "img"). Null supprime le rôle.
     */
    public function setRole(?string $role): self
    {
        $this->role = null !== $role ? trim($role) : null;

        return $this;
    }

    /**
     * Supprime le rôle du SVG.
     */
    public function removeRole(): self
    {
        $this->role = null;

        return $this;
    }

    /**
     * Méthode statique rapide : renvoie le contenu du fichier (brut).
     * Utile quand tu veux juste “inline” un SVG sans modification.
     *
     * @param string $path Chemin vers le fichier SVG
     *
     * @return string Contenu du fichier SVG
     *
     * @throws \InvalidArgumentException si le fichier n'existe pas, n'est pas lisible, ou est vide
     */
    public static function quickRender(string $path): string
    {
        return self::readFileStatic($path);
    }

    /**
     * Définit un titre accessible via un noeud <title>.
     *
     * @param string      $title Texte du titre
     * @param null|string $id    ID du <title>. Si null, un id sera généré au rendu.
     */
    public function setTitle(string $title, ?string $id = null): self
    {
        $title = trim($title);
        $this->title = ('' !== $title) ? $title : null;
        $this->titleId = null !== $id ? trim($id) : null;

        return $this;
    }

    /**
     * Supprime le titre accessible (<title> + aria-labelledby si géré par la classe).
     */
    public function removeTitle(): self
    {
        $this->title = null;
        $this->titleId = null;

        return $this;
    }

    /**
     * Lecture fichier (instance). Partage la logique avec quickRender().
     *
     * @param string $path Chemin vers le fichier
     *
     * @return string Contenu du fichier
     *
     * @throws \InvalidArgumentException si le fichier n'existe pas, n'est pas lisible, ou est vide
     */
    private function readFile(string $path): string
    {
        return self::readFileStatic($path);
    }

    /**
     * Lecture fichier (statique).
     *
     * @param string $path Chemin vers le fichier
     *
     * @return string Contenu du fichier
     *
     * @throws \InvalidArgumentException si le fichier n'existe pas, n'est pas lisible, ou est vide
     */
    private static function readFileStatic(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException("Le fichier SVG n'existe pas ou n'est pas lisible : ".$path);
        }

        $svg = file_get_contents($path);
        if (false === $svg || '' === trim($svg)) {
            throw new \InvalidArgumentException('Le fichier SVG est vide ou illisible : '.$path);
        }

        return $svg;
    }

    /**
     * Setter d'attribut "simple" :
     * - si $value est null ou vide => supprime l'attribut
     * - sinon => setAttribute
     *
     * @param \DOMElement $element Élément DOM à modifier
     * @param string      $name    Nom d'attribut
     * @param null|string $value   Valeur (null ou vide => suppression)
     */
    private function setAttribute(\DOMElement $element, string $name, ?string $value): void
    {
        $value = null !== $value ? trim($value) : null;

        if (null === $value || '' === $value) {
            if ($element->hasAttribute($name)) {
                $element->removeAttribute($name);
            }

            return;
        }

        $element->setAttribute($name, $value);
    }
}
