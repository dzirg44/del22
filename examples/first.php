<?php
require __DIR__ . '../vendor/autoload.php';

use SignNow\AmqStomp\Exceptions\StompException;
use SignNow\AmqStomp\Stomp;

try {
// make a connection
    $con = new Stomp("tcp://localhost:61613");
// connect
    $con->connect();
// send a message to the queue
    $con->send("/queue/test", "test");
    echo "Sent message with body 'test'\n";
// subscribe to the queue
    $con->subscribe("/queue/test");
// receive a message from the queue
    $msg = $con->readFrame();

// do what you want with the message
    if ($msg != null) {
        echo "Received message with body '$msg->body'\n";
        // mark the message as received in the queue
        $con->ack($msg);
    } else {
        echo "Failed to receive a message\n";
    }

// disconnect
    $con->disconnect();
} catch (StompException $e) {
    print_r("Exception {$e->getMessage()} \n");
}
