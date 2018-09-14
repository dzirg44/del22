<?php
require __DIR__ . '../vendor/autoload.php';

use SignNow\AmqStomp\Exceptions\StompException;
use SignNow\AmqStomp\Messages\Map;
use SignNow\AmqStomp\Stomp;

try {
// make a connection
    $con = new Stomp("tcp://localhost:61613");
// connect
    $con->connect();
// send a message to the queue
    $body = ["city" => "Belgrade", "name" => "Dejan"];
    $header = [];
    $header['transformation'] = 'jms-map-json';
    $mapMessage = new Map($body, $header);
    $con->send("/queue/test", $mapMessage);
    echo "Sending array: ";
    print_r($body);

    $con->subscribe("/queue/test", ['transformation' => 'jms-map-json']);
    /**
     * @var Map $msg
     */
    $msg = $con->readFrame();

// extract 
    if ($msg != null) {
        echo "Received array: ";
        print_r($msg->map);
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
?>