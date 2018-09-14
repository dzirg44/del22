<?php
declare(strict_types=1);

namespace SignNow\AmqStomp\Exceptions;

/**
 * Class StompException
 *
 * @package SignNow\AmqStomp\Exceptions
 */
class StompException extends \Exception
{
    /**
     * @var string
     */
    protected $details;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param string $details Stomp server error details
     */
    public function __construct($message = null, $code = 0, $details = '')
    {
        $this->details = $details;

        parent::__construct($message, $code);
    }

    /**
     * Stomp server error details
     *
     * @return string
     */
    public function getDetails()
    {
        return $this->details;
    }
}
