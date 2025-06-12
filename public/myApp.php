<?php

spl_autoload_register(function ($class) {
    $class = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
    require $class;
});

use App\Routing as Router;

$router = new Router();

// echo App\Classes\Home::class . PHP_EOL;

$router
->get('/', [App\Classes\Home::class, 'index'])
->get('/invoice' , [App\Classes\Invoice::class, 'index'])
->get('/invoice/create' , [App\Classes\Invoice::class, 'create']);
// ->post();
// ->get()

// print_r($router->allRoutes());
// echo $_SERVER['REQUEST_URI'] . PHP_EOL;


$router->resolve(requestURI: ($_SERVER['REQUEST_URI'] ?? '/'), requestMethod: $_SERVER['REQUEST_METHOD'] ?? 'GET');