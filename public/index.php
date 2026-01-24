<?php


/**
 *
 * Front controller du framework Koabana 
 */


declare(strict_types=1);

use GuzzleHttp\Psr7\ServerRequest;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Koabana\Bootstrap\ContainerFactory;
use Koabana\Bootstrap\RouterFactory;
use Koabana\Http\Kernel;
use Dotenv\Dotenv;
use Monolog\ErrorHandler;
use Psr\Log\LoggerInterface;

require dirname(__DIR__) . '/vendor/autoload.php';


// Chargement des variables d'environnement depuis le fichier .env
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();  
foreach ($_ENV as $k => $v) {
    putenv("$k=$v");
}


// Création du conteneur de dépendances
$container = ContainerFactory::create(dirname(__DIR__) . '/config/containers.php');

// Enregistrement du gestionnaire d'erreurs avec le logger
$logger = $container->get(LoggerInterface::class);
ErrorHandler::register($logger);

// Création du routeur à partir des définitions de routes
$router = RouterFactory::create($container, dirname(__DIR__) . '/config/routes.php');


// Création du noyau HTTP de l'application
$kernel = new Kernel($router);

// Gestion de la requête HTTP courante
$request = ServerRequest::fromGlobals();
$response = $kernel->handle($request);


// Envoi de la réponse HTTP au client
(new SapiEmitter())->emit($response);
