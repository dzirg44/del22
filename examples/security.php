<?php
require __DIR__ . '../vendor/autoload.php';

use SignNow\AmqStomp\Exceptions\StompException;
use SignNow\AmqStomp\Stomp;

try {
// make a connection
    $con = new Stomp("tcp://localhost:61613");
// use sync operations
    $con->sync = true;
// connect
    try {
        $con->connect("dejan", "test");
    } catch (StompException $e) {
        echo "dejan cannot connect\n";
        echo $e->getMessage() . "\n";
        echo $e->getDetails() . "\n\n\n";
    }

    $con->connect("guest", "password");

// send a message to the queue
    try {
        $con->send("/queue/test", "test");
        echo "Guest sent message with body 'test'\n";
    } catch (StompException $e) {
        echo "guest cannot send\n";
        echo $e->getMessage() . "\n";
        echo $e->getDetails() . "\n\n\n";
    }
// disconnect
    $con->disconnect();


    $con->connect("system", "manager");

// send a message to the queue
    $con->send("/queue/test", "test");
    echo "System manager sent message with body 'test'\n";

// disconnect
    $con->disconnect();
} catch (StompException $e) {
    print_r("Exception {$e->getMessage()} \n");
}


?>