<?php

declare(strict_types=1);

namespace Tests\Unit;

use Koabana\View\ViewContext;
use PHPUnit\Framework\TestCase;

final class ViewContextTest extends TestCase
{
    private ViewContext $viewContext;

    protected function setUp(): void
    {
        $this->viewContext = new ViewContext();
    }

    public function testStartSection(): void
    {
        $this->viewContext->start('content');
        echo 'Hello World';
        $this->viewContext->end('content');

        self::assertEquals('Hello World', $this->viewContext->section('content'));
    }

    public function testSectionWithDefault(): void
    {
        self::assertEquals('Default', $this->viewContext->section('non_existent', 'Default'));
    }

    public function testMultipleSections(): void
    {
        $this->viewContext->start('header');
        echo 'Header';
        $this->viewContext->end('header');

        $this->viewContext->start('footer');
        echo 'Footer';
        $this->viewContext->end('footer');

        self::assertEquals('Header', $this->viewContext->section('header'));
        self::assertEquals('Footer', $this->viewContext->section('footer'));
    }

    public function testAddStylesheet(): void
    {
        $this->viewContext->addStyleSheet('https://example.com/style.css');
        $styles = $this->viewContext->styleSheets();

        self::assertStringContainsString('style.css', $styles);
        self::assertStringContainsString('<link', $styles);
    }

    public function testStylesheetWithAttributes(): void
    {
        $this->viewContext->addStyleSheet('style.css', ['media' => 'screen']);
        $styles = $this->viewContext->styleSheets();

        self::assertStringContainsString('media="screen"', $styles);
    }

    public function testAddHeaderJs(): void
    {
        $this->viewContext->addHeaderJs('https://example.com/script.js');
        $scripts = $this->viewContext->headerJs();

        self::assertStringContainsString('script.js', $scripts);
        self::assertStringContainsString('<script', $scripts);
    }

    public function testAddFooterJs(): void
    {
        $this->viewContext->addFooterJs('https://example.com/script.js');
        $scripts = $this->viewContext->footerJs();

        self::assertStringContainsString('script.js', $scripts);
        self::assertStringContainsString('<script', $scripts);
    }

    public function testConfirmDeleteModal(): void
    {
        self::assertFalse($this->viewContext->isConfirmDeleteModalEnabled());
        
        $this->viewContext->enableConfirmDeleteModal();
        self::assertTrue($this->viewContext->isConfirmDeleteModalEnabled());
    }

    public function testSetActiveMenu(): void
    {
        $this->viewContext->setActiveMenu('accueil');
        self::assertTrue($this->viewContext->isActiveMenu('accueil'));
        self::assertFalse($this->viewContext->isActiveMenu('other'));
    }

    public function testSetInvalidMenuThrows(): void
    {
        self::expectException(\InvalidArgumentException::class);
        $this->viewContext->setActiveMenu('invalid_menu');
    }

    public function testReset(): void
    {
        $this->viewContext->start('content');
        echo 'Content';
        $this->viewContext->end('content');
        
        $this->viewContext->addStyleSheet('style.css');
        $this->viewContext->enableConfirmDeleteModal();

        $this->viewContext->reset();

        self::assertEquals('', $this->viewContext->section('content'));
        self::assertEquals('', $this->viewContext->styleSheets());
        self::assertFalse($this->viewContext->isConfirmDeleteModalEnabled());
    }

    public function testEscapeHtml(): void
    {
        $result = $this->viewContext->e('<script>alert("xss")</script>');
        self::assertStringNotContainsString('<script>', $result);
        self::assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testAddProfileInfo(): void
    {
        $profile = ['first_name' => 'John', 'email' => 'john@example.com'];
        $this->viewContext->addProfileInfo($profile);

        self::assertEquals($profile, $this->viewContext->getProfileInfos());
    }

    public function testGetProfileAttribute(): void
    {
        $profile = ['first_name' => 'Alice', 'last_name' => 'Smith', 'email' => 'alice@example.com'];
        $this->viewContext->addProfileInfo($profile);

        self::assertEquals('Alice', $this->viewContext->getProfileAttribute('first_name'));
        self::assertEquals('Smith', $this->viewContext->getProfileAttribute('last_name'));
    }

    public function testGetInvalidProfileAttributeReturnsFalse(): void
    {
        $profile = ['first_name' => 'Bob'];
        $this->viewContext->addProfileInfo($profile);

        self::assertFalse($this->viewContext->getProfileAttribute('invalid_attr'));
    }
}
