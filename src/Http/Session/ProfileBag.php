<?php

declare(strict_types=1);

namespace Koabana\Http\Session;

/**
 * ProfileBag : stockage des informations utilisateur en session.
 */
final class ProfileBag
{
    /**
     * @param SessionBag $bag
     */
    public function __construct(
        private SessionBag $bag,
    ) {}

    /**
     * L'utilisateur est-il loggé
     *
     * @return bool
     */
    public function isLogged(): bool
    {
        return null !== $this->getId();
    }

    /**
     * Récupère l'ID de l'utilisateur
     *
     * @return null|int
     */
    public function getId(): ?int
    {
        return $this->bag->get('user_id');
    }

    /**
     * Récupère le prénom de l'utilisateur
     *
     * @return string
     */
    public function getFirstname(): string
    {
        return (string) $this->bag->get('user_firstname', '');
    }

    /**
     * Récupère l'email de l'utilisateur
     *
     * @return string
     */
    public function getEmail(): string
    {
        return (string) $this->bag->get('user_email', '');
    }

    /**
     * Définit les informations de l'utilisateur
     *
     * @param array<string, mixed> $profile
     *
     * @return void
     */
    public function set(array $profile): void
    {
        foreach ($profile as $key => $value) {
            $this->bag->set($key, $value);
        }
    }

    /**
     * Vide les informations de l'utilisateur
     *
     * @return void
     */
    public function clear(): void
    {
        $this->bag->clear();
    }

    /**
     * Retourne les informations de l'utilisateur sous forme de tableau
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->getId(),
            'user_firstname' => $this->getFirstname(),
            'user_email' => $this->getEmail(),
        ];
    }
}
