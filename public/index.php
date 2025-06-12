<?php

declare(strict_types=1);

// $root = dirname(__DIR__) . DIRECTORY_SEPARATOR;

// define("APP_PATH", $root . 'app' . DIRECTORY_SEPARATOR);

// // require APP_PATH . "App.php";

// print_r(scandir(__DIR__));


// require_once './PaymentGateway/Paypal/Transaction.php';
// require_once './PaymentGateway/Paytm/Transaction.php';

// echo "These Classes are being used : " . PHP_EOL;
// spl_autoload_register(function ($class) {
//     $class = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
//     require $class;
//     var_dump($class);
// });

// require_once __DIR__ . '/../vendor/autoload.php';

// echo  __DIR__ . '/../vendor/autoload.php' . PHP_EOL;


// use PaymentGateway\Paypal\Transaction as Paypal;
// use PaymentGateway\Paypal\X as X;
// use PaymentGateway\Paytm\Transaction as Paytm;
// use PaymentGateway\Enums\Status;



// $x = new X();

// echo __DIR__ . '/myApp.php';
require_once  __DIR__ . '/myApp.php';
// register_shutdown_function(function() {
//     $error = error_get_last();
//     if ($error !== null) {
//         echo "Shutdown detected fatal error:\n";
//         print_r($error);
//     }
// });
// // error_log("Something went wrong in invoice module", 0); // Default: system log
// set_error_handler(function($errno, $errstr, $errfile, $errline){
    
//     echo "Handled error:\n";
//     echo "[$errno] $errstr in $errfile on line $errline\n";
//     error_log("[$errno] $errstr at $errfile:$errline" . PHP_EOL, 3, __DIR__ . "/my-errors.log");

//     return false;
// });



// error_reporting(E_ALL);
// trigger_error('This is a warning', E_USER_WARNING); // Will go to handler
// trigger_error('Fatal error here', E_USER_ERROR); // Will be caught by shutdown function

// echo "Hi;" . PHP_EOL;
// error_log(message: "Custom error" . PHP_EOL, message_type: 3, destination: __DIR__ . "/my-errors.log");


// require './Invoice.php';



// $obj = new Invoice(25, 'Invoice 1', '12345');

// $arr = serialize($obj);

// var_dump($arr);

// $ob2 = unserialize($arr);
// var_dump($ob2);

// echo $transaction->getStatus() . PHP_EOL;
// echo $transaction->status . PHP_EOL;


// echo $transaction->setStatus(Status::APPROVED -> value)->getStatus() . PHP_EOL;
