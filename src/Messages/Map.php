<?php
declare(strict_types=1);

namespace SignNow\AmqStomp\Messages;

/**
 * Message that contains a set of name-value pairs
 *
 * @package SignNow\AmqStomp\Messages
 */
class Map extends Message
{
    /**
     * @var mixed
     */
    public $map;

    /**
     * Constructor
     *
     * @param Frame|string|array $msg
     * @param array $headers
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    function __construct($msg, $headers = null)
    {
        if ($msg instanceof Frame) {
            $this->init($msg->command, $msg->headers, $msg->body);
            $this->map = json_decode($msg->body, true);
        } else {
            parent::__construct($msg, $headers);
            if ($this->headers == null) {
                $this->headers = [];
            }
            $this->headers['transformation'] = 'jms-map-json';
            $this->body = json_encode($msg);
        }
    }
}

?>