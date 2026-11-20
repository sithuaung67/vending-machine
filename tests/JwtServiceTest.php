<?php
declare(strict_types=1);
namespace Tests;

use App\Services\JwtService;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase
{
    public function test_issues_and_verifies_a_valid_token(): void
    {
        $jwt = new JwtService('secret-key', 3600);
        $token = $jwt->issue(['sub' => 7, 'role' => 'admin']);
        $claims = $jwt->verify($token);

        $this->assertSame(7, $claims['sub']);
        $this->assertSame('admin', $claims['role']);
    }

    public function test_rejects_a_tampered_token(): void
    {
        $jwt = new JwtService('secret-key', 3600);
        $token = $jwt->issue(['sub' => 1]);
        $this->expectException(\RuntimeException::class);
        $jwt->verify($token . 'tampered');
    }

    public function test_rejects_an_expired_token(): void
    {
        $jwt = new JwtService('secret-key', -10); // already expired
        $token = $jwt->issue(['sub' => 1]);
        $this->expectException(\RuntimeException::class);
        $jwt->verify($token);
    }
}
