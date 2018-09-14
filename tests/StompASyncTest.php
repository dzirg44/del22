<?php
declare(strict_types=1);

namespace SignNow\AmqStomp\Tests;

use PHPUnit_Framework_TestCase;
use SignNow\AmqStomp\Stomp;

/**
 * Class StompASyncTest
 *
 * @package SignNow\AmqStomp\Tests
 */
class StompASyncTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Stomp
     */
    private $stomp;

    /**
     * Tests Stomp->connect(), send(), and subscribe() - out of order. the messages should be received in FIFO order.
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testAsyncSub()
    {
        $this->assertTrue($this->stomp->connect());

        $this->assertTrue($this->stomp->send('/queue/test', 'test 1'));
        $this->assertTrue($this->stomp->send('/queue/test', 'test 2'));
        $this->assertTrue($this->stomp->subscribe('/queue/test'));

        $frame = $this->stomp->readFrame();
        $this->assertEquals($frame->body, 'test 1', 'test 1 was not received!');
        $this->stomp->ack($frame);

        $frame = $this->stomp->readFrame();
        $this->assertEquals($frame->body, 'test 2', 'test 2 was not received!');
        $this->stomp->ack($frame);
    }

    /**
     * Prepares the environment before running a test.
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    protected function setUp()
    {
        parent::setUp();

        $this->stomp = new Stomp('tcp://localhost:61613');
        $this->stomp->sync = false;
    }

    /**
     * Cleans up the environment after running a test.
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    protected function tearDown()
    {
        $this->stomp->disconnect();
        $this->stomp = null;
        parent::tearDown();
    }
}
