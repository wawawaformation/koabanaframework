<?php

declare(strict_types=1);

namespace Koabana\Http\Session;

/**
 * FlashBag : gestion des messages flash en session.
 *
 * Permet d'ajouter des messages temporaires (succès, erreur, info...)
 * qui seront affichés à l'utilisateur lors de la prochaine requête,
 * puis supprimés.
 *
 * @return void
 *              Les messages sont stockés par type (info, success, error, etc.)
 *              dans la session, sous la clé _flash.
 *
 * Exemple d'utilisation :
 *   $flash = new FlashBag($sessionBag);
 *   $flash->add('success', 'Votre profil a été mis à jour.');
 *
 * @author david
 */
final class FlashBag
{
    /**
     * Clé de stockage des messages flash dans la session.
     */
    private const KEY = '_flash';

    /**
     * Initialise le FlashBag avec un SessionBag et prépare la structure si besoin.
     *
     * @param SessionBag $session Bag de session utilisé pour stocker les messages
     */
    public function __construct(private SessionBag $session)
    {
        $current = $this->session->get(self::KEY);

        if (!\is_array($current)) {
            $this->session->set(self::KEY, []);
        }
    }

    /**
     * Ajoute un message flash d'un certain type.
     *
     * @param string $type    Type de message (info, success, error...)
     * @param string $message Message à stocker
     */
    public function add(string $type, string $message): void
    {
        $type = \strtolower(\trim($type));
        if ('' === $type) {
            $type = 'info';
        }

        if ('' === $message) {
            return;
        }

        /** @var array<string, list<string>> $all */
        $all = $this->session->get(self::KEY, []);

        $all[$type] ??= [];
        $all[$type][] = $message;

        $this->session->set(self::KEY, $all);
    }

    /**
     * Récupère tous les messages flash de tous les types et les consomme (vide après lecture).
     *
     * @return array<string, list<string>> Tableau associatif type => liste de messages
     */
    public function all(): array
    {
        /** @var array<string, list<string>> $all */
        $all = $this->session->get(self::KEY, []);

        // Consommation : on vide après lecture
        $this->session->set(self::KEY, []);

        return $all;
    }

    /**
     * Récupère et consomme tous les messages d'un type donné.
     *
     * @param string $type Type de message (info, success, error...)
     *
     * @return list<string> Liste des messages pour ce type
     */
    public function get(string $type): array
    {
        $type = \strtolower(\trim($type));
        if ('' === $type) {
            $type = 'info';
        }

        /** @var array<string, list<string>> $all */
        $all = $this->session->get(self::KEY, []);

        $messages = $all[$type] ?? [];
        unset($all[$type]);

        $this->session->set(self::KEY, $all);

        return $messages;
    }

    /**
     * Vide tous les messages flash de la session.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->session->set(self::KEY, []);
    }

    /**
     * Indique s'il existe au moins un message flash (tous types ou pour un type donné).
     *
     * @param null|string $type Type de message à tester, ou null pour tous
     *
     * @return bool true si au moins un message existe
     */
    public function has(?string $type = null): bool
    {
        /** @var array<string, list<string>> $all */
        $all = $this->session->get(self::KEY, []);

        if (null === $type) {
            foreach ($all as $messages) {
                if (!empty($messages)) {
                    return true;
                }
            }

            return false;
        }

        $type = \strtolower(\trim($type));
        if ('' === $type) {
            $type = 'info';
        }

        return !empty($all[$type] ?? []);
    }
}
