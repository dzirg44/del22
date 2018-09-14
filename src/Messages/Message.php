<?php
declare(strict_types=1);

namespace SignNow\AmqStomp\Messages;

/**
 * Basic text stomp message
 *
 * @package SignNow\AmqStomp\Messages
 */
class Message extends Frame
{
    /**
     * Message constructor.
     *
     * @param $body
     * @param null $headers
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function __construct($body, $headers = null)
    {
        parent::__construct("SEND", $headers, $body);

    }
}

?>