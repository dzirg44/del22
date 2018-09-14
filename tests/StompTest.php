<?php
declare(strict_types=1);

namespace SignNow\AmqStomp\Tests;

use PHPUnit_Framework_TestCase;
use SignNow\AmqStomp\Messages\Bytes;
use SignNow\AmqStomp\Messages\Frame;
use SignNow\AmqStomp\Messages\Map;
use SignNow\AmqStomp\Stomp;

/**
 * Class StompTest
 * @package SignNow\AmqStomp\Tests
 */
class StompTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Stomp
     */
    private $stomp;
    private $broker = 'tcp://127.0.0.1:61613';
    private $queue = '/queue/test';
    private $topic = '/topic/test';

    /**
     * Tests Stomp->hasFrameToRead()
     *
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testHasFrameToRead()
    {
        if (!$this->stomp->isConnected()) {
            $this->stomp->connect();
        }

        $this->stomp->setReadTimeout(5);

        $this->assertFalse($this->stomp->hasFrameToRead(), 'Has frame to read when non expected');

        $this->stomp->send($this->queue, 'testHasFrameToRead');

        $this->stomp->subscribe($this->queue, array('ack' => 'client', 'activemq.prefetchSize' => 1));

        $this->assertTrue($this->stomp->hasFrameToRead(), 'Did not have frame to read when expected');

        $frame = $this->stomp->readFrame();

        $this->assertInstanceOf(Frame::class, $frame, 'Frame expected');

        $this->stomp->ack($frame);

        $this->stomp->disconnect();

        $this->stomp->setReadTimeout(60);
    }

    /**
     * Tests Stomp->ack()
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testAck()
    {
        if (!$this->stomp->isConnected()) {
            $this->stomp->connect();
        }

        $messages = array();
        for ($x = 0; $x < 100; ++$x) {
            $this->stomp->send($this->queue, $x);
            $messages[$x] = 'sent';
        }

        $this->stomp->disconnect();

        for ($y = 0; $y < 100; $y += 10) {
            $this->stomp->connect();

            $this->stomp->subscribe($this->queue, array('ack' => 'client', 'activemq.prefetchSize' => 1));

            for ($x = $y; $x < $y + 10; ++$x) {
                $frame = $this->stomp->readFrame();
                $this->assertInstanceOf(Frame::class, $frame, 'Frame expected. Iteration ' . $y . ':' . $x);
                $this->assertArrayHasKey(
                    $frame->body,
                    $messages,
                    $frame->body . ' is not in the list of messages to ack'
                );
                $this->assertEquals(
                    'sent',
                    $messages[$frame->body],
                    $frame->body . ' has been marked acked, but has been received again.'
                );
                $messages[$frame->body] = 'acked';

                $this->assertTrue($this->stomp->ack($frame), "Unable to ack {$frame->headers['message-id']}");
            }

            $this->stomp->disconnect();
        }

        $un_acked_messages = array();

        foreach ($messages as $key => $value) {
            if ($value == 'sent') {
                $un_acked_messages[] = $key;
            }
        }

        $this->assertEquals(
            0,
            count($un_acked_messages),
            'Remaining messages to ack' . var_export($un_acked_messages, true)
        );
    }

    /**
     * Tests Stomp->abort()
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testAbort()
    {
        $this->stomp->setReadTimeout(1);
        if (!$this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $this->stomp->begin("tx1");
        $this->assertTrue($this->stomp->send($this->queue, 'testSend', array("transaction" => "tx1")));
        $this->stomp->abort("tx1");

        $this->stomp->subscribe($this->queue);
        $frame = $this->stomp->readFrame();
        $this->assertFalse($frame);
        $this->stomp->unsubscribe($this->queue);
        $this->stomp->disconnect();
    }

    /**
     * Tests Stomp->connect()
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testConnect()
    {
        $this->assertTrue($this->stomp->connect());
        $this->assertTrue($this->stomp->isConnected());
    }

    /**
     * Tests Stomp->disconnect()
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testDisconnect()
    {
        if (!$this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $this->assertTrue($this->stomp->isConnected());
        $this->stomp->disconnect();
        $this->assertFalse($this->stomp->isConnected());
    }

    /**
     * Tests Stomp->getSessionId()
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testGetSessionId()
    {
        if (!$this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $this->assertNotNull($this->stomp->getSessionId());
    }

    /**
     * Tests Stomp->isConnected()
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testIsConnected()
    {
        $this->stomp->connect();
        $this->assertTrue($this->stomp->isConnected());
        $this->stomp->disconnect();
        $this->assertFalse($this->stomp->isConnected());
    }

    /**
     * Tests Stomp->readFrame()
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testReadFrame()
    {
        if (!$this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $this->stomp->send($this->queue, 'testReadFrame');
        $this->stomp->subscribe($this->queue);
        $frame = $this->stomp->readFrame();
        $this->assertInstanceOf(Frame::class, $frame, 'Frame expected.');
        $this->assertEquals('testReadFrame', $frame->body, 'Body of test frame does not match sent message');
        $this->stomp->ack($frame);
        $this->stomp->unsubscribe($this->queue);
    }

    /**
     * Tests Stomp->send()
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testSend()
    {
        if (!$this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $this->assertTrue($this->stomp->send($this->queue, 'testSend'));
        $this->stomp->subscribe($this->queue);
        $frame = $this->stomp->readFrame();
        $this->assertInstanceOf(Frame::class, $frame, 'Frame expected.');
        $this->assertEquals('testSend', $frame->body, 'Body of test frame does not match sent message');
        $this->stomp->ack($frame);
        $this->stomp->unsubscribe($this->queue);
    }

    /**
     * Tests Stomp->subscribe()
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testSubscribe()
    {
        if (!$this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $this->assertTrue($this->stomp->subscribe($this->queue));
        $this->stomp->unsubscribe($this->queue);
    }

    /**
     * Tests Stomp message transformation - json map
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testJsonMapTransformation()
    {
        if (!$this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $body = array("city" => "Belgrade", "name" => "Dejan");
        $header = array();
        $header['transformation'] = 'jms-map-json';
        $mapMessage = new Map($body, $header);
        $this->stomp->send($this->queue, $mapMessage);
        $this->stomp->subscribe($this->queue, array('transformation' => 'jms-map-json'));
        /**
         * @var Map $msg
         */
        $msg = $this->stomp->readFrame();
        $this->assertInstanceOf(Map::class, $msg, 'Map expected.');
        $this->assertEquals($msg->map, $body);
        $this->stomp->ack($msg);
        $this->stomp->disconnect();
    }

    /**
     * Tests Stomp byte messages
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testByteMessages()
    {
        if (!$this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $body = "test";
        $mapMessage = new Bytes($body);
        $this->stomp->send($this->queue, $mapMessage);

        $this->stomp->subscribe($this->queue);
        $msg = $this->stomp->readFrame();
        $this->assertEquals($msg->body, $body);
        $this->stomp->ack($msg);
        $this->stomp->disconnect();
    }

    /**
     * Tests Stomp->unsubscribe()
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testUnsubscribe()
    {
        if (!$this->stomp->isConnected()) {
            $this->stomp->connect();
        }
        $this->stomp->subscribe($this->queue);
        $this->assertTrue($this->stomp->unsubscribe($this->queue));
    }

    /**
     *
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testDurable()
    {
        $this->subscribe();
        sleep(2);
        $this->produce();
        sleep(2);
        $this->consume();
    }

    /**
     * Prepares the environment before running a test.
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    protected function setUp()
    {
        parent::setUp();

        $this->stomp = new Stomp($this->broker);
        $this->stomp->sync = false;
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->stomp = null;
        parent::tearDown();
    }

    /**
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    protected function produce()
    {
        $producer = new Stomp($this->broker);
        $producer->sync = false;
        $producer->connect("system", "manager");
        $producer->send($this->topic, "test message", array('persistent' => 'true'));
        $producer->disconnect();
    }

    /**
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    protected function subscribe()
    {
        $consumer = new Stomp($this->broker);
        $consumer->sync = false;
        $consumer->clientId = "test";
        $consumer->connect("system", "manager");
        $consumer->subscribe($this->topic);
        $consumer->unsubscribe($this->topic);
        $consumer->disconnect();
    }

    /**
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    protected function consume()
    {
        $consumer2 = new Stomp($this->broker);
        $consumer2->sync = false;
        $consumer2->clientId = "test";
        $consumer2->setReadTimeout(1);
        $consumer2->connect("system", "manager");
        $consumer2->subscribe($this->topic);

        $frame = $consumer2->readFrame();
        $this->assertEquals($frame->body, "test message");
        if ($frame != null) {
            $consumer2->ack($frame);
        }

        $consumer2->disconnect();
    }
}
