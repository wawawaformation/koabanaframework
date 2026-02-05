<?php

declare(strict_types=1);

namespace Koabana\Http\Session;

/**
 * SessionBag : gestion d'un sous-ensemble de données de session.
 *
 * Permet d'accéder, modifier, supprimer et lister des valeurs dans un tableau de session,
 * typiquement utilisé pour isoler des groupes de clés (ex : flash, user, panier).
 *
 * Les opérations sont faites par référence sur le tableau fourni au constructeur,
 *
 * @return void
 *              ce qui garantit la synchronisation avec la session PHP native ou tout autre stockage.
 *
 * Exemple d'utilisation :
 *   $bag = new SessionBag($_SESSION['user']);
 *   $bag->set('id', 42);
 *   $id = $bag->get('id');
 *
 * @author david
 */
final class SessionBag
{
    /**
     * Initialise le bag avec une référence sur le tableau de données.
     *
     * @param array<string, mixed> &$data Tableau associatif de session (par référence)
     */
    public function __construct(private array &$data) {}

    /**
     * Retourne la valeur associée à la clé, ou la valeur par défaut si absente.
     *
     * @param string $key     Clé à lire
     * @param mixed  $default Valeur de retour si la clé n'existe pas
     *
     * @return mixed Valeur stockée ou défaut
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Définit la valeur associée à la clé.
     *
     * @param string $key   Clé à écrire
     * @param mixed  $value Valeur à stocker
     */
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Vérifie si la clé existe dans le bag.
     *
     * @param string $key Clé à tester
     *
     * @return bool true si la clé existe
     */
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->data);
    }

    /**
     * Supprime la clé du bag.
     *
     * @param string $key Clé à supprimer
     *
     * @return void
     */
    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * Retourne toutes les données du bag.
     *
     * @return array<string, mixed> Tableau associatif complet
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Vide toutes les données du bag.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->data = [];
    }

    /**
     * Retourne un sous-bag pour une clé donnée.
     *
     * Si la clé n'existe pas ou n'est pas un tableau, un tableau vide est créé.
     *
     * @param string $key Clé du sous-bag
     *
     * @return SessionBag Nouveau bag pour la clé spécifiée
     */
    public function bag(string $key): SessionBag
    {
        if (!isset($this->data[$key]) || !\is_array($this->data[$key])) {
            $this->data[$key] = [];
        }

        return new SessionBag($this->data[$key]);
    }
}
