<?php

namespace Sentinel\Tests\Unit\Core;

use Sentinel\Core\Request;
use Sentinel\Tests\TestCase;

class RequestTest extends TestCase
{
    public function testMethodResolution()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $request = new Request();
        $this->assertEquals('POST', $request->method());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();
        $this->assertEquals('GET', $request->method());
    }

    public function testUriTrimsQueryString()
    {
        $_SERVER['REQUEST_URI'] = '/api/v1/events?test=123';
        $request = new Request();
        $this->assertEquals('/api/v1/events', $request->uri());
    }

    public function testHeaderNormalization()
    {
        $_SERVER['HTTP_X_API_KEY'] = 'sk_test123';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        
        $request = new Request();
        
        $this->assertEquals('sk_test123', $request->header('x_api_key'));
        $this->assertEquals('application/json', $request->header('accept'));
        $this->assertNull($request->header('missing_header'));
    }

    public function testBearerTokenExtraction()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer my_token_abc';
        $request = new Request();
        
        $this->assertEquals('my_token_abc', $request->bearerToken());
    }

    public function testBearerTokenExtractionReturnsNullIfMissing()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic 12345';
        $request = new Request();
        
        $this->assertNull($request->bearerToken());
    }

    public function testIpAddressResolutionDefaultsToRemotAddr()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $request = new Request();
        
        $this->assertEquals('192.168.1.100', $request->ip());
    }

    public function testIpAddressResolutionUsesForwardedForIfPresent()
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '8.8.8.8';
        $request = new Request();
        
        $this->assertEquals('8.8.8.8', $request->ip());
    }
}
