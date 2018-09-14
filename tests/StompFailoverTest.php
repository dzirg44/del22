<?php
declare(strict_types=1);

namespace SignNow\AmqStomp\Tests;

use PHPUnit_Framework_TestCase;
use SignNow\AmqStomp\Stomp;

/**
 * Class StompFailoverTest
 *
 * @package SignNow\AmqStomp\Tests
 */
class StompFailoverTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Stomp
     */
    private $stomp;

    /**
     * Tests Stomp->connect()
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    public function testFailoverConnect()
    {
        $this->assertTrue($this->stomp->connect());
    }

    /**
     * Prepares the environment before running a test.
     * @throws \SignNow\AmqStomp\Exceptions\StompException
     */
    protected function setUp()
    {
        parent::setUp();

        $this->stomp = new Stomp('failover://(tcp://localhost:61614,tcp://localhost:61613)?randomize=false');
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
