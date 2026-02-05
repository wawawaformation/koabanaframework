<?php

declare(strict_types=1);

namespace Koabana\Model\Entity;

/**
 * Entité de démonstration pour les tests et exemples.
 */
class TestEntity extends AbstractEntity
{
    private ?string $tests = null;

    private string $autres = '';

    private bool $isBool = false;

    /**
     * @return null|string
     */
    public function getTests(): ?string
    {
        return $this->tests;
    }

    /**
     * @param null|string $tests
     *
     * @return $this
     */
    public function setTests(?string $tests = null): self
    {
        $this->tests = $tests;

        return $this;
    }

    /**
     * @return string
     */
    public function getAutres(): string
    {
        return $this->autres;
    }

    /**
     * @param string $autres
     *
     * @return $this
     */
    public function setAutres(string $autres): self
    {
        $this->autres = $autres;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBool(): bool
    {
        return $this->isBool;
    }

    /**
     * @param bool $isBool
     *
     * @return $this
     */
    public function setIsBool(bool $isBool): self
    {
        $this->isBool = $isBool;

        return $this;
    }
}
