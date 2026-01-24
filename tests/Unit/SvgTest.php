<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\View\Svg;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SvgTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $this->tmpDir = $base.DIRECTORY_SEPARATOR.'koabana_svg_tests_'.bin2hex(random_bytes(6));

        if (!mkdir($this->tmpDir, 0o777, true) && !is_dir($this->tmpDir)) {
            throw new \RuntimeException('Impossible de créer le répertoire temporaire de test.');
        }
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
        parent::tearDown();
    }

    /* -------------------------------------------------
     * Constructeur : validation fichier / XML / <svg>
     * ------------------------------------------------- */

    public function testConstructorThrowsWhenFileMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Svg($this->tmpDir.'/missing.svg');
    }

    public function testConstructorThrowsWhenFileEmpty(): void
    {
        $path = $this->writeFile('empty.svg', '');

        $this->expectException(\InvalidArgumentException::class);
        new Svg($path);
    }

    public function testConstructorThrowsWhenInvalidXml(): void
    {
        $path = $this->writeFile('invalid.svg', '<svg><g></svg'); // XML invalide

        $this->expectException(\InvalidArgumentException::class);
        new Svg($path);
    }

    public function testConstructorThrowsWhenNoSvgTag(): void
    {
        $path = $this->writeFile('no-svg.svg', '<html><body>nope</body></html>');

        $this->expectException(\InvalidArgumentException::class);
        new Svg($path);
    }

    /* -------------------------------------------------
     * Constructeur : état initial + normalisation
     * ------------------------------------------------- */

    public function testConstructorExtractsInitialManagedAttributes(): void
    {
        $xml = $this->svgWithViewBox(
            attributes: 'class="a   b  c" role="img" aria-hidden="true" aria-label="X" fill="red" width="10" height="20"',
        );
        $path = $this->writeFile('initial.svg', $xml);

        $svg = new Svg($path);
        $root = $this->loadRenderedRoot($svg->render());

        // Les attributs gérés sont présents au rendu (état initial = extrait)
        self::assertSame('a b c', $this->normalizeSpaces($root->getAttribute('class')));
        self::assertSame('img', $root->getAttribute('role'));
        self::assertSame('true', $root->getAttribute('aria-hidden'));
        self::assertSame('X', $root->getAttribute('aria-label'));
        self::assertSame('red', $root->getAttribute('fill'));

        // width/height supprimés car viewBox présent
        self::assertFalse($root->hasAttribute('width'));
        self::assertFalse($root->hasAttribute('height'));
    }

    public function testConstructorRemovesWidthHeightWhenViewBoxExists(): void
    {
        $path = $this->writeFile('with-viewbox.svg', $this->svgWithViewBox(
            attributes: 'width="10" height="20"',
        ));

        $svg = new Svg($path);
        $root = $this->loadRenderedRoot($svg->render());

        self::assertFalse($root->hasAttribute('width'));
        self::assertFalse($root->hasAttribute('height'));
        self::assertTrue($root->hasAttribute('viewBox'));
    }

    public function testConstructorKeepsWidthHeightWhenNoViewBox(): void
    {
        $xml = $this->svgWithoutViewBox(attributes: 'width="10" height="20"');
        $path = $this->writeFile('no-viewbox.svg', $xml);

        $svg = new Svg($path);
        $root = $this->loadRenderedRoot($svg->render());

        self::assertSame('10', $root->getAttribute('width'));
        self::assertSame('20', $root->getAttribute('height'));
        self::assertFalse($root->hasAttribute('viewBox'));
    }

    /* -------------------------------------------------
     * Render : remplacement strict (pas de fusion)
     * ------------------------------------------------- */

    public function testRenderDoesNotMergeManagedAttributesItReplacesThem(): void
    {
        $xml = $this->svgWithViewBox(
            attributes: 'class="old" role="img" aria-hidden="true" fill="red"',
        );
        $path = $this->writeFile('replace.svg', $xml);

        $svg = new Svg($path);

        // On remplace tout (pas de fusion)
        $svg->clear()
            ->addClass('new')
            ->setRole('presentation')
            ->setAriaAttribute('label', 'Icône de test')
            ->setFill('currentColor')
        ;

        $root = $this->loadRenderedRoot($svg->render());

        self::assertSame('new', $root->getAttribute('class'));
        self::assertSame('presentation', $root->getAttribute('role'));
        self::assertSame('Icône de test', $root->getAttribute('aria-label'));
        self::assertSame('currentColor', $root->getAttribute('fill'));

        // Les anciens attributs ne doivent plus être présents
        self::assertFalse($root->hasAttribute('aria-hidden'));
    }

    public function testRenderRemovesAllOriginalAriaAttributesWhenNewAriaIsEmpty(): void
    {
        $xml = $this->svgWithViewBox(attributes: 'aria-hidden="true" aria-label="X" aria-expanded="false"');
        $path = $this->writeFile('aria-clean.svg', $xml);

        $svg = new Svg($path);

        // On veut explicitement "aucun aria-*"
        $svg->clearAriaAttributes();

        $root = $this->loadRenderedRoot($svg->render());

        self::assertFalse($root->hasAttribute('aria-hidden'));
        self::assertFalse($root->hasAttribute('aria-label'));
        self::assertFalse($root->hasAttribute('aria-expanded'));
    }

    public function testRenderRemovesOriginalClassRoleFillWhenNewValuesAreEmptyOrNull(): void
    {
        $xml = $this->svgWithViewBox(attributes: 'class="old" role="img" fill="red"');
        $path = $this->writeFile('managed-clean.svg', $xml);

        $svg = new Svg($path);

        // On supprime tout via API
        $svg->clearClasses()
            ->setRole(null)
            ->removeFill()
        ;

        $root = $this->loadRenderedRoot($svg->render());

        self::assertFalse($root->hasAttribute('class'));
        self::assertFalse($root->hasAttribute('role'));
        self::assertFalse($root->hasAttribute('fill'));
    }

    /* -------------------------------------------------
     * API : classes
     * ------------------------------------------------- */

    public function testAddClassDoesNotDuplicateTrimsAndRemoveClassWorks(): void
    {
        $path = $this->writeFile('classes.svg', $this->svgWithViewBox());
        $svg = new Svg($path);

        $svg->clearClasses()
            ->addClass('  a ')
            ->addClass('a')
            ->addClass('b')
            ->addClass('  ') // ignoré
            ->removeClass('a')
        ;

        $root = $this->loadRenderedRoot($svg->render());
        self::assertSame('b', $root->getAttribute('class'));
    }

    public function testRemoveClassOnUnknownClassDoesNothing(): void
    {
        $path = $this->writeFile('classes-unknown.svg', $this->svgWithViewBox());
        $svg = new Svg($path);

        $svg->clearClasses()
            ->addClass('a')
            ->removeClass('x')
        ;

        $root = $this->loadRenderedRoot($svg->render());
        self::assertSame('a', $root->getAttribute('class'));
    }

    /* -------------------------------------------------
     * API : aria
     * ------------------------------------------------- */

    public function testAriaPrefixHandlingAndRemoveAriaAttribute(): void
    {
        $path = $this->writeFile('aria.svg', $this->svgWithViewBox());
        $svg = new Svg($path);

        $svg->clearAriaAttributes()
            ->setAriaAttribute('hidden', 'true')      // sans préfixe
            ->setAriaAttribute('aria-label', 'Test')  // avec préfixe
            ->removeAriaAttribute('hidden')
        ;

        $root = $this->loadRenderedRoot($svg->render());

        self::assertFalse($root->hasAttribute('aria-hidden'));
        self::assertSame('Test', $root->getAttribute('aria-label'));
    }

    public function testSetAriaAttributeEmptyValueRemovesAttributeAtRender(): void
    {
        $xml = $this->svgWithViewBox(attributes: 'aria-label="X"');
        $path = $this->writeFile('aria-empty.svg', $xml);

        $svg = new Svg($path);

        // on remplace aria-label par une valeur vide => au render, setAttribute() supprime
        $svg->setAriaAttribute('label', '   ');

        $root = $this->loadRenderedRoot($svg->render());
        self::assertFalse($root->hasAttribute('aria-label'));
    }

    /* -------------------------------------------------
     * API : role / fill / clear()
     * ------------------------------------------------- */

    public function testSetRoleNullRemovesRole(): void
    {
        $xml = $this->svgWithViewBox(attributes: 'role="img"');
        $path = $this->writeFile('role.svg', $xml);

        $svg = new Svg($path);
        $svg->setRole(null);

        $root = $this->loadRenderedRoot($svg->render());
        self::assertFalse($root->hasAttribute('role'));
    }

    public function testSetFillAndRemoveFill(): void
    {
        $xml = $this->svgWithViewBox(attributes: 'fill="red"');
        $path = $this->writeFile('fill.svg', $xml);

        $svg = new Svg($path);

        $svg->setFill('currentColor');
        $root1 = $this->loadRenderedRoot($svg->render());
        self::assertSame('currentColor', $root1->getAttribute('fill'));

        $svg->removeFill();
        $root2 = $this->loadRenderedRoot($svg->render());
        self::assertFalse($root2->hasAttribute('fill'));
    }

    public function testClearResetsAllManagedState(): void
    {
        $xml = $this->svgWithViewBox(attributes: 'class="old" role="img" aria-hidden="true" fill="red"');
        $path = $this->writeFile('clear.svg', $xml);

        $svg = new Svg($path);

        // clear() doit supprimer l'état (et donc au render, supprimer les attributs gérés)
        $svg->clear();

        $root = $this->loadRenderedRoot($svg->render());

        self::assertFalse($root->hasAttribute('class'));
        self::assertFalse($root->hasAttribute('role'));
        self::assertFalse($root->hasAttribute('fill'));
        self::assertFalse($root->hasAttribute('aria-hidden'));
    }

    /* -------------------------------------------------
     * Non-régression : contenu interne conservé
     * ------------------------------------------------- */

    public function testRenderDoesNotTouchChildAttributes(): void
    {
        $xml = $this->svgWithViewBox(attributes: 'fill="red"', inner: '<path d="M0 0" fill="blue" id="p1"/>');
        $path = $this->writeFile('child.svg', $xml);

        $svg = new Svg($path);

        // On change le fill de la racine
        $svg->setFill('currentColor');

        $doc = new \DOMDocument();
        $ok = $doc->loadXML($svg->render(), LIBXML_NONET);
        self::assertTrue($ok);

        $root = $doc->getElementsByTagName('svg')->item(0);
        self::assertInstanceOf(\DOMElement::class, $root);
        self::assertSame('currentColor', $root->getAttribute('fill'));

        $pathNode = $doc->getElementsByTagName('path')->item(0);
        self::assertInstanceOf(\DOMElement::class, $pathNode);

        // Le fill du child doit rester "blue" (la classe ne modifie que la racine <svg>)
        self::assertSame('blue', $pathNode->getAttribute('fill'));
        self::assertSame('p1', $pathNode->getAttribute('id'));
    }

    /* -------------------------------------------------
     * quickRender()
     * ------------------------------------------------- */

    public function testQuickRenderReturnsRawFileContent(): void
    {
        $xml = $this->svgWithViewBox(attributes: 'class="x"');
        $path = $this->writeFile('quick.svg', $xml);

        $raw = Svg::quickRender($path);
        self::assertSame($xml, $raw);
    }

    public function testQuickRenderThrowsWhenFileMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Svg::quickRender($this->tmpDir.'/missing.svg');
    }

    public function testQuickRenderThrowsWhenFileEmpty(): void
    {
        $path = $this->writeFile('quick-empty.svg', '');

        $this->expectException(\InvalidArgumentException::class);
        Svg::quickRender($path);
    }

    public function testSetTitleAddsTitleNodeAndAriaLabelledby(): void
    {
        $path = $this->writeFile('title.svg', $this->svgWithViewBox(attributes: ''));
        $svg = new Svg($path);

        $svg->clear()
            ->setTitle('Fermer', 'my-title')
        ;

        $doc = new \DOMDocument();
        $ok = $doc->loadXML($svg->render(), LIBXML_NONET);
        self::assertTrue($ok);

        $root = $doc->getElementsByTagName('svg')->item(0);
        self::assertInstanceOf(\DOMElement::class, $root);

        // aria-labelledby posé
        self::assertSame('my-title', $root->getAttribute('aria-labelledby'));

        // <title id="my-title">Fermer</title> présent
        $titles = $root->getElementsByTagName('title');
        self::assertSame(1, $titles->length);

        $title = $titles->item(0);
        self::assertInstanceOf(\DOMElement::class, $title);
        self::assertSame('my-title', $title->getAttribute('id'));
        self::assertSame('Fermer', $title->textContent);
    }

    public function testRemoveTitleRemovesTitleNodeAndAriaLabelledby(): void
    {
        // SVG qui contient déjà un title + aria-labelledby dans la source
        $xml = $this->svgWithViewBox(
            attributes: 'aria-labelledby="old-title"',
            inner: '<title id="old-title">Ancien</title><path d="M0 0" />',
        );
        $path = $this->writeFile('title-remove.svg', $xml);

        $svg = new Svg($path);

        // On supprime explicitement le title géré
        $svg->removeTitle();

        $doc = new \DOMDocument();
        $ok = $doc->loadXML($svg->render(), LIBXML_NONET);
        self::assertTrue($ok);

        $root = $doc->getElementsByTagName('svg')->item(0);
        self::assertInstanceOf(\DOMElement::class, $root);

        self::assertFalse($root->hasAttribute('aria-labelledby'));

        $titles = $root->getElementsByTagName('title');
        self::assertSame(0, $titles->length);
    }

    /* -------------------------------------------------
     * Helpers
     * ------------------------------------------------- */

    private function writeFile(string $name, string $content): string
    {
        $path = $this->tmpDir.DIRECTORY_SEPARATOR.$name;
        file_put_contents($path, $content);

        return $path;
    }

    private function loadRenderedRoot(string $xml): \DOMElement
    {
        $doc = new \DOMDocument();
        $ok = $doc->loadXML($xml, LIBXML_NONET);

        self::assertTrue($ok, 'Le rendu SVG n’est pas un XML valide.');
        $root = $doc->getElementsByTagName('svg')->item(0);

        self::assertInstanceOf(\DOMElement::class, $root, 'Le rendu ne contient pas de balise <svg>.');

        return $root;
    }

    private function svgWithViewBox(string $attributes = '', string $inner = '<path d="M0 0" />'): string
    {
        $attributes = trim($attributes);
        if ('' !== $attributes) {
            $attributes = ' '.$attributes;
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"'.$attributes.'>'
            .$inner
            .'</svg>';
    }

    private function svgWithoutViewBox(string $attributes = '', string $inner = '<path d="M0 0" />'): string
    {
        $attributes = trim($attributes);
        if ('' !== $attributes) {
            $attributes = ' '.$attributes;
        }

        return '<svg xmlns="http://www.w3.org/2000/svg"'.$attributes.'>'
            .$inner
            .'</svg>';
    }

    private function normalizeSpaces(string $s): string
    {
        $s = trim($s);
        if ('' === $s) {
            return '';
        }

        return preg_replace('/\s+/', ' ', $s) ?? $s;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if (false === $items) {
            return;
        }

        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $this->removeDir($path);

                continue;
            }

            @unlink($path);
        }

        @rmdir($dir);
    }
}
