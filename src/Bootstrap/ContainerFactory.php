<?php

declare(strict_types=1);

namespace Koabana\Bootstrap;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

final class ContainerFactory
{
    /**
     * Construit le container PHP-DI à partir d'un fichier de définitions.
     *
     * @param string               $definitionsFile Chemin vers le fichier containers.php
     * @param array<string, mixed> $overrides       Définitions qui surchargent celles du fichier (optionnel)
     */
    public static function create(string $definitionsFile, array $overrides = []): ContainerInterface
    {
        if (!is_file($definitionsFile) || !is_readable($definitionsFile)) {
            throw new \InvalidArgumentException('Fichier de définitions introuvable ou illisible : '.$definitionsFile);
        }

        $definitions = require $definitionsFile;

        if (!is_array($definitions)) {
            throw new \InvalidArgumentException('Le fichier de définitions doit retourner un tableau : '.$definitionsFile);
        }

        /** @var array<string, mixed> $definitions */
        if ([] !== $overrides) {
            $definitions = array_replace($definitions, $overrides);
        }

        $builder = new ContainerBuilder();

        // Autowiring activé par défaut (PHP-DI), mais c’est explicite.
        $builder->useAutowiring(true);

        // Pas d’annotations (tu peux activer plus tard si tu en as besoin).
        $builder->useAttributes(true);

        $builder->addDefinitions($definitions);

        return $builder->build();
    }
}
