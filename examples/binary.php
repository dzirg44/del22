<?php
require __DIR__ . '../vendor/autoload.php';

use SignNow\AmqStomp\Exceptions\StompException;
use SignNow\AmqStomp\Messages\Bytes;
use SignNow\AmqStomp\Stomp;

try {
    // make a connection
    $con = new Stomp("tcp://localhost:61613");
    // connect
    $con->connect();
    // send a message to the queue
    $body = "test";
    $bytesMessage = new Bytes($body);
    $con->send("/queue/test", $bytesMessage);

    print_r("Sending message: $body \n");

    $con->subscribe("/queue/test");
    $msg = $con->readFrame();

    // extract
    if ($msg != null) {
        print_r("Received message: {$msg->body} \n");
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
