<?php
require __DIR__ . '../vendor/autoload.php';

use SignNow\AmqStomp\Exceptions\StompException;
use SignNow\AmqStomp\Stomp;

/*
 To successfully run this example, you must first start the broker with stomp+ssl enabled.
 You can do that by executing:
 $ ${ACTIVEMQ_HOME}/bin/activemq xbean:activemq-connectivity.xml
 Then you can execute this example with:
 $ php connectivity.php
*/

// include a library
// make a connection
try {
    $con = new Stomp("failover://(tcp://localhost:61614,ssl://localhost:61612)?randomize=false");
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
