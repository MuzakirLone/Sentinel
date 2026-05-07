<?php

namespace Sentinel\Tests\Unit\Core;

use Sentinel\Core\Auth;
use Sentinel\Tests\TestCase;

class AuthTest extends TestCase
{
    public function testStartSessionInitializesSession()
    {
        $this->assertEquals(PHP_SESSION_NONE, session_status());
        Auth::startSession();
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    public function testCsrfTokenGeneratesAndValidates()
    {
        $token = Auth::csrfToken();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));

        $this->assertTrue(Auth::verifyCsrfToken($token));
        $this->assertFalse(Auth::verifyCsrfToken('invalid_token'));
        $this->assertFalse(Auth::verifyCsrfToken(''));
    }

    public function testLoginSetsSessionData()
    {
        Auth::login(1, 'admin@example.com', 'Admin User');
        
        $this->assertTrue(Auth::check());
        $this->assertEquals(1, Auth::userId());
        
        $user = Auth::user();
        $this->assertEquals(1, $user['id']);
        $this->assertEquals('admin@example.com', $user['email']);
        $this->assertEquals('Admin User', $user['name']);
    }

    public function testLogoutClearsSessionData()
    {
        Auth::login(1, 'admin@example.com', 'Admin User');
        $this->assertTrue(Auth::check());

        Auth::logout();
        $this->assertFalse(Auth::check());
        $this->assertNull(Auth::userId());
    }

    public function testPasswordHashing()
    {
        $password = 'secret123';
        $hash = Auth::hashPassword($password);

        $this->assertNotEquals($password, $hash);
        $this->assertTrue(Auth::verifyPassword($password, $hash));
        $this->assertFalse(Auth::verifyPassword('wrong123', $hash));
    }

    public function testApiKeyGenerationAndHashing()
    {
        $key = Auth::generateApiKey();
        $this->assertStringStartsWith('sk_', $key);
        $this->assertTrue(strlen($key) > 32);

        $hash = Auth::hashApiKey($key);
        $this->assertEquals(64, strlen($hash));
        $this->assertNotEquals($key, $hash);
    }
}
