<?php

declare(strict_types=1);

namespace Koabana\Database;

/**
 * Usine de connexions à la base de données.
 */
class BDDFactory
{
    /**
     * Connexion PDO mémorisée par l'instance.
     */
    protected ?MyPDO $connection = null;

    public function __construct() {}

    /**
     * REtourne une connexion PDO à la BDD
     *
     * @return MyPDO
     *
     * @throws \RuntimeException
     */
    public function getConnection(): MyPDO
    {
        $dsn = getenv('DB_DSN');

        if (false === $dsn || '' === $dsn) {
            throw new \RuntimeException('DSN de la base de données non configuré.');
        }

        $user = getenv('DB_USER') ?: '';
        $password = getenv('DB_PASSWORD') ?: '';

        if ('sqlite::memory:' === $dsn) {
            $user = null;
            $password = null;
        }

        if (null === $this->connection) {
            $this->connection = new MyPDO($dsn, $user, $password);
        }

        return $this->connection;
    }
}
