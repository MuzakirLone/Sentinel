<?php

namespace Sentinel\Tests\Integration;

use Sentinel\Tests\TestCase;
use Sentinel\Core\Middleware\HmacMiddleware;
use Sentinel\Core\Request;

class ApiEndpointTest extends TestCase
{
    public function testHmacMiddlewareRejectsWithoutNonceOrSignature()
    {
        $db = $this->createMockDatabase();
        $middleware = new HmacMiddleware($db, 'secret123');

        // Missing signature completely
        $_SERVER['HTTP_X_TIMESTAMP'] = time();
        $request = new Request();
        
        $this->assertFalse($middleware->handle($request));
    }

    public function testHmacMiddlewareRejectsMismatchedSignature()
    {
        $db = $this->createMockDatabase();
        // It will try to lookup the api key. Mock it to return a valid secret.
        $db->method('queryOne')->willReturn([
            'api_secret' => 'correct_secret',
            'admin_user_id' => 1
        ]);

        $middleware = new HmacMiddleware($db, 'secret123');

        $timestamp = time();
        $nonce = 'random_nonce';
        
        $_SERVER['HTTP_X_API_KEY'] = 'sk_test_key';
        $_SERVER['HTTP_X_TIMESTAMP'] = (string) $timestamp;
        $_SERVER['HTTP_X_NONCE'] = $nonce;
        $_SERVER['HTTP_X_SIGNATURE'] = 'invalid_hash_signature';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/v1/events';

        $request = new Request();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid HMAC signature');
        $this->expectExceptionCode(401);
        
        $middleware->handle($request);
    }

    public function testHmacMiddlewareRejectsTimestampDrift()
    {
        $db = $this->createMockDatabase();
        $middleware = new HmacMiddleware($db, 'secret123');

        $timestamp = time() - 600; // 10 minutes ago
        $nonce = 'random_nonce';
        
        $_SERVER['HTTP_X_API_KEY'] = 'sk_test_key';
        $_SERVER['HTTP_X_TIMESTAMP'] = (string) $timestamp;
        $_SERVER['HTTP_X_NONCE'] = $nonce;
        $_SERVER['HTTP_X_SIGNATURE'] = 'somehash';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/v1/events';

        $request = new Request();
        
        $this->expectException(\Exception::class);
        // Note: the message checks for drift > 300s
        $this->expectExceptionMessageMatches('/Request timestamp drift/');
        $this->expectExceptionCode(401);
        
        $middleware->handle($request);
    }

    public function testHmacMiddlewarePreventsReplayAttacks()
    {
        $db = $this->createMockDatabase();
        
        // Mock the DB to throw a duplicate key exception on nonce insertion
        $db->expects($this->once())
           ->method('execute')
           ->willThrowException(new \Exception('duplicate key value violates unique constraint'));

        $middleware = new HmacMiddleware($db, 'secret123');

        $timestamp = time();
        $nonce = 'reused_nonce';
        
        $_SERVER['HTTP_X_API_KEY'] = 'sk_test_key';
        $_SERVER['HTTP_X_TIMESTAMP'] = (string) $timestamp;
        $_SERVER['HTTP_X_NONCE'] = $nonce;
        $_SERVER['HTTP_X_SIGNATURE'] = 'somehash';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/v1/events';

        $request = new Request();
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Nonce already used. Replay attack prevented.');
        $this->expectExceptionCode(401);
        
        $middleware->handle($request);
    }
}
