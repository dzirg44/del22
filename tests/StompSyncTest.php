<?php
declare(strict_types=1);

namespace SignNow\AmqStomp\Tests;

use PHPUnit_Framework_TestCase;
use SignNow\AmqStomp\Stomp;

/**
 * Class StompSyncTest
 * @package SignNow\AmqStomp\Tests
 */
class StompSyncTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Stomp
     */
    private $stomp;

    /**
     * Tests Stomp->connect(), send() and subscribe() in order.
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testSyncSub()
    {
        $this->assertTrue($this->stomp->connect());
        $this->assertTrue($this->stomp->subscribe('/queue/test'));
        $this->assertTrue($this->stomp->send('/queue/test', 'test 1'));
        $this->assertTrue($this->stomp->send('/queue/test', 'test 2'));


        $this->stomp->setReadTimeout(5);

        $frame = $this->stomp->readFrame();
        $this->assertEquals('test 1', $frame->body, 'test 1 not received!');
        $this->stomp->ack($frame);

        $frame = $this->stomp->readFrame();
        $this->assertEquals('test 2', $frame->body, 'test 2 not received!');
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
        $this->stomp->sync = true;
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
