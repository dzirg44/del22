<?php
declare(strict_types=1);

namespace SignNow\AmqStomp;

use SignNow\AmqStomp\Exceptions\StompException;
use SignNow\AmqStomp\Messages\Frame as StompFrame;
use SignNow\AmqStomp\Messages\Map as StompMessageMap;

/**
 * A Stomp Connection
 */
class Stomp
{
    /**
     * Perform request synchronously
     *
     * @var boolean
     */
    public $sync = false;

    /**
     * Default prefetch size
     *
     * @var int
     */
    public $prefetchSize = 1;

    /**
     * Client id used for durable subscriptions
     *
     * @var string
     */
    public $clientId = null;

    protected $brokerUri = null;
    protected $socker = null;
    protected $hosts = [];
    protected $params = [];
    protected $subscriptions = [];
    protected $defaultPort = 61613;
    protected $currentHost = -1;
    protected $attempts = 10;
    protected $username = '';
    protected $password = '';
    protected $sessionId;
    protected $readTimeoutSeconds = 60;
    protected $readTimeoutMilliseconds = 0;
    protected $connectTimeoutSeconds = 60;

    /**
     * Constructor
     *
     * @param string $brokerUri Broker URL
     * @throws StompException
     */
    public function __construct($brokerUri)
    {
        $this->brokerUri = $brokerUri;
        $this->init();
    }

    /**
     * Connect to server
     *
     * @param string $username
     * @param string $password
     *
     * @return boolean
     * @throws StompException
     */
    public function connect($username = '', $password = '')
    {
        $this->makeConnection();
        if ($username != '') {
            $this->username = $username;
        }
        if ($password != '') {
            $this->password = $password;
        }
        $headers = ['login' => $this->username, 'passcode' => $this->password];
        if ($this->clientId != null) {
            $headers["client-id"] = $this->clientId;
        }
        $frame = new StompFrame("CONNECT", $headers);
        $this->writeFrame($frame);
        $frame = $this->readFrame();
        if ($frame instanceof StompFrame && $frame->command == 'CONNECTED') {
            $this->sessionId = $frame->headers["session"];
            return true;
        } else {
            if ($frame instanceof StompFrame) {
                throw new StompException("Unexpected command: {$frame->command}", 0, $frame->body);
            } else {
                throw new StompException("Connection not acknowledged");
            }
        }
    }

    /**
     * Check if client session has ben established
     *
     * @return boolean
     */
    public function isConnected()
    {
        return !empty($this->sessionId) && is_resource($this->socker);
    }

    /**
     * Current stomp session ID
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Send a message to a destination in the messaging system
     *
     * @param string $destination Destination queue
     * @param string|StompFrame $msg Message
     * @param array $properties
     * @param boolean $sync Perform request synchronously
     *
     * @return boolean
     * @throws StompException
     */
    public function send($destination, $msg, $properties = [], $sync = null)
    {
        if ($msg instanceof StompFrame) {
            $msg->headers['destination'] = $destination;
            if (is_array($properties)) {
                $msg->headers = array_merge($msg->headers, $properties);
            }
            $frame = $msg;
        } else {
            $headers = $properties;
            $headers['destination'] = $destination;
            $frame = new StompFrame('SEND', $headers, $msg);
        }
        $this->prepareReceipt($frame, $sync);
        $this->writeFrame($frame);
        return $this->waitForReceipt($frame, $sync);
    }

    /**
     * Register to listen to a given destination
     *
     * @param string $destination Destination queue
     * @param array $properties
     * @param boolean $sync Perform request synchronously
     *
     * @return boolean
     * @throws StompException
     */
    public function subscribe($destination, $properties = null, $sync = null)
    {
        $headers = ['ack' => 'client'];
        $headers['activemq.prefetchSize'] = $this->prefetchSize;
        if ($this->clientId != null) {
            $headers["activemq.subcriptionName"] = $this->clientId;
        }
        if (isset($properties)) {
            foreach ($properties as $name => $value) {
                $headers[$name] = $value;
            }
        }
        $headers['destination'] = $destination;
        $frame = new StompFrame('SUBSCRIBE', $headers);
        $this->prepareReceipt($frame, $sync);
        $this->writeFrame($frame);
        if ($this->waitForReceipt($frame, $sync) == true) {
            $this->subscriptions[$destination] = $properties;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Remove an existing subscription
     *
     * @param string $destination
     * @param array $properties
     * @param boolean $sync Perform request synchronously
     *
     * @return boolean
     * @throws StompException
     */
    public function unsubscribe($destination, $properties = null, $sync = null)
    {
        $headers = [];
        if (isset($properties)) {
            foreach ($properties as $name => $value) {
                $headers[$name] = $value;
            }
        }
        $headers['destination'] = $destination;
        $frame = new StompFrame('UNSUBSCRIBE', $headers);
        $this->prepareReceipt($frame, $sync);
        $this->writeFrame($frame);
        if ($this->waitForReceipt($frame, $sync) == true) {
            unset($this->subscriptions[$destination]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Start a transaction
     *
     * @param string $transactionId
     * @param boolean $sync Perform request synchronously
     *
     * @return boolean
     * @throws StompException
     */
    public function begin($transactionId = null, $sync = null)
    {
        $headers = [];
        if (isset($transactionId)) {
            $headers['transaction'] = $transactionId;
        }
        $frame = new StompFrame('BEGIN', $headers);
        $this->prepareReceipt($frame, $sync);
        $this->writeFrame($frame);
        return $this->waitForReceipt($frame, $sync);
    }

    /**
     * Commit a transaction in progress
     *
     * @param string $transactionId
     * @param boolean $sync Perform request synchronously
     *
     * @return boolean
     * @throws StompException
     */
    public function commit($transactionId = null, $sync = null)
    {
        $headers = [];
        if (isset($transactionId)) {
            $headers['transaction'] = $transactionId;
        }
        $frame = new StompFrame('COMMIT', $headers);
        $this->prepareReceipt($frame, $sync);
        $this->writeFrame($frame);
        return $this->waitForReceipt($frame, $sync);
    }

    /**
     * Roll back a transaction in progress
     *
     * @param string $transactionId
     * @param boolean $sync Perform request synchronously
     *
     * @return bool
     * @throws StompException
     */
    public function abort($transactionId = null, $sync = null)
    {
        $headers = [];
        if (isset($transactionId)) {
            $headers['transaction'] = $transactionId;
        }
        $frame = new StompFrame('ABORT', $headers);
        $this->prepareReceipt($frame, $sync);
        $this->writeFrame($frame);
        return $this->waitForReceipt($frame, $sync);
    }

    /**
     * Acknowledge consumption of a message from a subscription
     * Note: This operation is always asynchronous
     *
     * @param string|StompFrame $message Message ID
     * @param string $transactionId
     *
     * @return boolean
     * @throws StompException
     */
    public function ack($message, $transactionId = null)
    {
        if ($message instanceof StompFrame) {
            $headers = $message->headers;
            if (isset($transactionId)) {
                $headers['transaction'] = $transactionId;
            }
            $frame = new StompFrame('ACK', $headers);
            $this->writeFrame($frame);
            return true;
        } else {
            $headers = [];
            if (isset($transactionId)) {
                $headers['transaction'] = $transactionId;
            }
            $headers['message-id'] = $message;
            $frame = new StompFrame('ACK', $headers);
            $this->writeFrame($frame);
            return true;
        }
    }

    /**
     * Graceful disconnect from the server
     *
     * @throws StompException
     */
    public function disconnect()
    {
        $headers = [];

        if ($this->clientId != null) {
            $headers["client-id"] = $this->clientId;
        }

        if (is_resource($this->socker)) {
            $this->writeFrame(new StompFrame('DISCONNECT', $headers));
            fclose($this->socker);
        }
        $this->socker = null;
        $this->sessionId = null;
        $this->currentHost = -1;
        $this->subscriptions = [];
        $this->username = '';
        $this->password = '';
    }

    /**
     * Set timeout to wait for content to read
     *
     * @param int $seconds Seconds to wait for a frame
     * @param int $milliseconds Milliseconds to wait for a frame
     */
    public function setReadTimeout($seconds, $milliseconds = 0)
    {
        $this->readTimeoutSeconds = $seconds;
        $this->readTimeoutMilliseconds = $milliseconds;
    }

    /**
     * Read response frame from server
     *
     * @return StompFrame|bool False when no frame to read
     * @throws StompException
     */
    public function readFrame()
    {
        if (!$this->hasFrameToRead()) {
            return false;
        }

        $rb = 20971520; // Change by Kiall - Max 20 MB
        $data = '';
        $end = false;

        do {
            $read = fread($this->socker, $rb);
            if ($read === false) {
                $this->reconnect();
                return $this->readFrame();
            }
            $data .= $read;
            if (strpos($data, "\x00") !== false) {
                $end = true;
                $data = rtrim($data, "\n");
            }
            $len = strlen($data);
        } while ($len < 2 || $end == false);

        list ($header, $body) = explode("\n\n", $data, 2);
        $header = explode("\n", $header);
        $headers = [];
        $command = null;
        foreach ($header as $v) {
            if (isset($command)) {
                list ($name, $value) = explode(':', $v, 2);
                $headers[$name] = $value;
            } else {
                $command = $v;
            }
        }
        $frame = new StompFrame($command, $headers, trim($body));
        if (isset($frame->headers['transformation']) && $frame->headers['transformation'] == 'jms-map-json') {
            return new StompMessageMap($frame);
        } else {
            return $frame;
        }
    }

    /**
     * Check if there is a frame to read
     *
     * @return boolean
     * @throws StompException
     */
    public function hasFrameToRead()
    {
        $read = [$this->socker];
        $write = null;
        $except = null;

        $has_frame_to_read = @stream_select(
            $read,
            $write,
            $except,
            $this->readTimeoutSeconds,
            $this->readTimeoutMilliseconds
        );

        if ($has_frame_to_read !== false) {
            $has_frame_to_read = count($read);
        }


        if ($has_frame_to_read === false) {
            throw new StompException('Check failed to determine if the socket is readable');
        } else {
            if ($has_frame_to_read > 0) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Graceful object desruction
     *
     * @throws StompException
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Initialize connection
     *
     * @throws StompException
     */
    protected function init()
    {
        $pattern = "|^(([a-zA-Z]+)://)+\(*([a-zA-Z0-9\.:/i,-]+)\)*\??([a-zA-Z0-9=]*)$|i";
        if (preg_match($pattern, $this->brokerUri, $regs)) {
            $scheme = $regs[2];
            $hosts = $regs[3];
            $params = $regs[4];
            if ($scheme != "failover") {
                $this->processUrl($this->brokerUri);
            } else {
                $urls = explode(",", $hosts);
                foreach ($urls as $url) {
                    $this->processUrl($url);
                }
            }
            if ($params != null) {
                parse_str($params, $this->params);
            }
        } else {
            throw new StompException("Bad Broker URL {$this->brokerUri}");
        }
    }

    /**
     * Process broker URL
     *
     * @param string $url Broker URL
     * @throws StompException
     */
    protected function processUrl($url)
    {
        $parsed = parse_url($url);
        if ($parsed) {
            array_push($this->hosts, [$parsed['host'], $parsed['port'], $parsed['scheme']]);
        } else {
            throw new StompException("Bad Broker URL $url");
        }
    }

    /**
     * Make socket connection to the server
     *
     * @throws StompException
     */
    protected function makeConnection()
    {
        if (count($this->hosts) == 0) {
            throw new StompException("No broker defined");
        }

        // force disconnect, if previous established connection exists
        $this->disconnect();

        $i = $this->currentHost;
        $att = 0;
        $connected = false;
        $connectErrno = null;
        $connectErrstr = null;

        while (!$connected && $att++ < $this->attempts) {
            if (isset($this->params['randomize']) && $this->params['randomize'] == 'true') {
                $i = rand(0, count($this->hosts) - 1);
            } else {
                $i = ($i + 1) % count($this->hosts);
            }
            $broker = $this->hosts[$i];
            $host = $broker[0];
            $port = $broker[1];
            $scheme = $broker[2];
            if ($port == null) {
                $port = $this->defaultPort;
            }
            if ($this->socker != null) {
                fclose($this->socker);
                $this->socker = null;
            }
            $this->socker = @fsockopen(
                $scheme . '://' . $host,
                $port,
                $connectErrno,
                $connectErrstr,
                $this->connectTimeoutSeconds
            );
            if (!is_resource($this->socker) && $att >= $this->attempts && !array_key_exists($i + 1, $this->hosts)) {
                throw new StompException("Could not connect to $host:$port ($att/{$this->attempts})");
            } else {
                if (is_resource($this->socker)) {
                    $connected = true;
                    $this->currentHost = $i;
                    break;
                }
            }
        }
        if (!$connected) {
            throw new StompException("Could not connect to a broker");
        }
    }

    /**
     * Prepare frame receipt
     *
     * @param StompFrame $frame
     * @param boolean $sync
     */
    protected function prepareReceipt(StompFrame $frame, $sync)
    {
        $receive = $this->sync;
        if ($sync !== null) {
            $receive = $sync;
        }
        if ($receive == true) {
            $frame->headers['receipt'] = md5(microtime());
        }
    }

    /**
     * Wait for receipt
     *
     * @param StompFrame $frame
     * @param boolean $sync
     * @return boolean
     * @throws StompException
     */
    protected function waitForReceipt(StompFrame $frame, $sync)
    {

        $receive = $this->sync;
        if ($sync !== null) {
            $receive = $sync;
        }
        if ($receive == true) {
            $id = (isset($frame->headers['receipt'])) ? $frame->headers['receipt'] : null;
            if ($id == null) {
                return true;
            }
            $frame = $this->readFrame();
            if ($frame instanceof StompFrame && $frame->command == 'RECEIPT') {
                if ($frame->headers['receipt-id'] == $id) {
                    return true;
                } else {
                    throw new StompException(
                        "Unexpected receipt id {$frame->headers['receipt-id']}",
                        0,
                        $frame->body
                    );
                }
            } else {
                if ($frame instanceof StompFrame) {
                    throw new StompException("Unexpected command {$frame->command}", 0, $frame->body);
                } else {
                    throw new StompException("Receipt not received");
                }
            }
        }
        return true;
    }

    /**
     * Write frame to server
     *
     * @param StompFrame $stompFrame
     * @throws StompException
     */
    protected function writeFrame(StompFrame $stompFrame)
    {
        if (!is_resource($this->socker)) {
            throw new StompException('Socket connection hasn\'t been established');
        }

        $data = $stompFrame->__toString();
        $r = fwrite($this->socker, $data, strlen($data));
        if ($r === false || $r == 0) {
            $this->reconnect();
            $this->writeFrame($stompFrame);
        }
    }

    /**
     * Reconnects and renews subscriptions (if there were any)
     * Call this method when you detect connection problems
     *
     * @throws StompException
     */
    protected function reconnect()
    {
        $subscriptions = $this->subscriptions;

        $this->connect($this->username, $this->password);
        foreach ($subscriptions as $dest => $properties) {
            $this->subscribe($dest, $properties);
        }
    }
}
