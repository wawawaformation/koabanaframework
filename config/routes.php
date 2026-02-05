<?php
declare(strict_types=1);

return [
    ['GET', '/demo', \Koabana\Controller\DemoController::class],
    ['GET', '/demo/form', [\Koabana\Controller\DemoController::class, 'form']],
    ['POST', '/demo/submit', [\Koabana\Controller\DemoController::class, 'submit']],
    ['GET', '/demo/tests', [\Koabana\Controller\DemoController::class, 'testBags']],
    ['GET', '/demo/session/set', [\Koabana\Controller\DemoController::class, 'sessionSet']],
    ['GET', '/demo/session/view', [\Koabana\Controller\DemoController::class, 'sessionView']],
    ['GET', '/demo/profile/login', [\Koabana\Controller\DemoController::class, 'profileLogin']],
    ['GET', '/demo/profile/logout', [\Koabana\Controller\DemoController::class, 'profileLogout']],
    ['GET', '/demo/flash/add', [\Koabana\Controller\DemoController::class, 'flashAdd']],
    ['GET', '/', [\Koabana\Controller\HomeController::class, 'index']],
];