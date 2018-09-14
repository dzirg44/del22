<?php
declare(strict_types=1);

namespace SignNow\AmqStomp\Tests;

use PHPUnit_Framework_TestCase;
use SignNow\AmqStomp\Messages\Frame;
use SignNow\AmqStomp\Stomp;

/**
 * Class StompSslTest
 *
 * @package SignNow\AmqStomp\Tests
 */
class StompSslTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Stomp
     */
    private $stomp;
    private $broker = 'ssl://localhost:61612';
    private $queue = '/queue/test';

    /**
     * Tests Stomp->abort()
     */
    public function testAbort()
    {
        // TODO Auto-generated StompTest->testAbort()
        $this->markTestIncomplete("abort test not implemented");
    }

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
    }

    /**
     * Tests Stomp->begin()
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testBegin()
    {
        //@todo Auto-generated StompTest->testBegin()
        $this->markTestIncomplete("begin test not implemented");
        $this->stomp->begin(/* parameters */);
    }

    /**
     * Tests Stomp->commit()
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testCommit()
    {
        // TODO Auto-generated StompTest->testCommit()
        $this->markTestIncomplete("commit test not implemented");
        $this->stomp->commit(/* parameters */);
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
        $this->assertInstanceOf(Frame::class, $frame, 'Frame expected');
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
}
