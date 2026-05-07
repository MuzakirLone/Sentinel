<?php

namespace Sentinel\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Sentinel\Core\Database;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset superglobals for a clean slate
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI'    => '/',
            'HTTP_HOST'      => 'localhost',
            'REMOTE_ADDR'    => '127.0.0.1',
        ];
    }

    /**
     * Create a mocked Database instance for unit testing.
     */
    protected function createMockDatabase()
    {
        return $this->createMock(Database::class);
    }
}
