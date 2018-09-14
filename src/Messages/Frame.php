<?php
declare(strict_types=1);

namespace SignNow\AmqStomp\Messages;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

use SignNow\AmqStomp\Exceptions\StompException;

/**
 * Class Frame
 * Stomp Frames are messages that are sent and received on a stomp connection.
 *
 * @package SignNow\AmqStomp\Messages
 */
class Frame
{
    public $command;
    public $headers = [];
    public $body;

    /**
     * Constructor
     *
     * @param string $command
     * @param array $headers
     * @param string $body
     * @throws StompException
     */
    public function __construct($command = null, $headers = null, $body = null)
    {
        $this->init($command, $headers, $body);
    }

    /**
     * Convert frame to transportable string
     *
     * @return string
     */
    public function __toString()
    {
        $data = $this->command . "\n";

        foreach ($this->headers as $name => $value) {
            $data .= $name . ": " . $value . "\n";
        }

        $data .= "\n" . $this->body . "\x00";
        return $data;
    }

    /**
     * @param null $command
     * @param null $headers
     * @param null $body
     * @throws StompException
     */
    protected function init($command = null, $headers = null, $body = null)
    {
        $this->command = $command;
        if ($headers != null) {
            $this->headers = $headers;
        }
        $this->body = $body;

        if ($this->command == 'ERROR') {
            throw new StompException($this->headers['message'], 0, $this->body);
        }
    }
}
