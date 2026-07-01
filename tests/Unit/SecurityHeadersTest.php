<?php

namespace Tests\Unit;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_content_security_policy_allows_browser_speech_recognition_endpoints(): void
    {
        $middleware = new SecurityHeaders;

        $response = $middleware->handle(Request::create('/'), fn () => response('ok'));

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("connect-src 'self'", $csp);
        $this->assertStringContainsString('https://www.google.com', $csp);
        $this->assertStringContainsString('https://*.google.com', $csp);
        $this->assertStringContainsString('https://*.googleapis.com', $csp);
        $this->assertStringContainsString('https://*.gstatic.com', $csp);
    }

    public function test_permissions_policy_allows_microphone_on_same_origin(): void
    {
        $middleware = new SecurityHeaders;

        $response = $middleware->handle(Request::create('/'), fn () => response('ok'));

        $this->assertSame(
            'geolocation=(), microphone=(self), camera=()',
            $response->headers->get('Permissions-Policy')
        );
    }
}
