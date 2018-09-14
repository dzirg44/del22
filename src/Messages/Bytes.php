<?php
declare(strict_types=1);

namespace SignNow\AmqStomp\Messages;

/**
 * Class Bytes
 * Message that contains a stream of uninterpreted bytes
 *
 * @package SignNow\AmqStomp\Messages
 */
class Bytes extends Message
{
    /**
     * Constructor
     *
     * @param string $body
     * @param array $headers
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function __construct($body, $headers = null)
    {
        parent::__construct($body, $headers);
        if ($this->headers == null) {
            $this->headers = [];
        }
        $this->headers['content-length'] = mb_strlen($body);
    }
}
