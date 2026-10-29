<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Tests\Support\CreatesWelfareFixtures;
use Tests\TestCase;

class ForwardedAccessTest extends TestCase
{
    use CreatesWelfareFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['security.trusted_proxies' => ['127.0.0.1', '::1'], 'session.secure' => null, 'session.domain' => null]);
    }

    protected function tearDown(): void
    {
        TrustProxies::flushState();
        parent::tearDown();
    }

    private function forwarded(string $address = '127.0.0.1'): static
    {
        return $this->withServerVariables([
            'REMOTE_ADDR' => $address, 'HTTP_HOST' => '127.0.0.1:8000',
            'HTTP_X_FORWARDED_HOST' => 'forwarded.example.test',
            'HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_X_FORWARDED_PORT' => '443',
        ]);
    }

    public function test_trusted_https_forwarding_generates_secure_assets_forms_and_cookies(): void
    {
        $response = $this->forwarded()->get('/login')->assertOk();
        $response->assertSee('https://forwarded.example.test/css/foundation-auth.css', false)
            ->assertSee('action="https://forwarded.example.test/login"', false)
            ->assertDontSee('http://forwarded.example.test', false);
        $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isSecure());
        $this->assertNull($cookie->getDomain());
    }

    public function test_forwarded_login_redirects_and_keeps_the_authenticated_session(): void
    {
        $user = $this->staff([]);
        $response = $this->forwarded()->post('/login', ['email' => $user->email, 'password' => 'test-password']);
        $response->assertRedirect()->assertSessionHas('security.complete', true);
        $this->assertStringStartsWith('https://forwarded.example.test', $response->headers->get('Location'));
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertOk();
    }

    public function test_unknown_proxies_cannot_override_the_request_origin(): void
    {
        $response = $this->forwarded('203.0.113.10')->get('/login')->assertOk();
        $response->assertDontSee('action="https://forwarded.example.test/login"', false);
    }

    public function test_direct_local_http_does_not_require_an_https_only_cookie(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_HOST' => '127.0.0.1:8000'])->get('/login')->assertOk();
        $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
        $this->assertNotNull($cookie);
        $this->assertFalse($cookie->isSecure());
    }
}
