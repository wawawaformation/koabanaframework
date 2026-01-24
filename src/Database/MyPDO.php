<?php

declare(strict_types=1);

namespace Koabana\Database;

/**
 * Extension légère de PDO avec des options par défaut cohérentes.
 *
 * Cette classe encapsule la configuration initiale de PDO afin de fournir
 * un comportement standard dans l'application :
 * - Gestion des erreurs via exceptions (ERRMODE_EXCEPTION)
 * - Fetch mode par défaut en tableau associatif (FETCH_ASSOC)
 * - Désactivation de l'émulation des requêtes préparées (sécurité / cohérence)
 *
 * Note :
 * - L'option MySQL `MYSQL_ATTR_INIT_COMMAND` est appliquée uniquement si le DSN
 *   cible MySQL (préfixe `mysql:`).
 */
class MyPDO extends \PDO
{
    /**
     * Construit une connexion PDO et applique les attributs par défaut.
     *
     * @param string      $dsn      DSN PDO (ex: mysql:..., sqlite:..., sqlite::memory:)
     * @param null|string $username Nom d'utilisateur (si applicable)
     * @param null|string $password Mot de passe (si applicable)
     *
     * @throws \PDOException En cas d'échec de connexion ou de configuration PDO
     */
    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
    ) {
        try {
            parent::__construct($dsn, $username, $password);

            $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $this->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

            // Applique uniquement l'option spécifique MySQL lorsque le DSN cible MySQL
            if (\str_starts_with($dsn, 'mysql:')) {
                $this->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES 'utf8'");
            }
        } catch (\PDOException $e) {
            // Gestion personnalisée des erreurs de connexion PDO
            throw new \PDOException('Échec de la connexion à la base de données : '.$e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
