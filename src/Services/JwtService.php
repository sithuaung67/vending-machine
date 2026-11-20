<?php
declare(strict_types=1);
namespace App\Services;

/** Minimal, dependency-free JWT (HS256) — encode/verify for the REST API. */
final class JwtService
{
    public function __construct(
        private string $secret,
        private int $ttl = 3600,
    ) {}

    public function issue(array $claims): string
    {
        $now = time();
        $payload = array_merge($claims, ['iat' => $now, 'exp' => $now + $this->ttl]);
        $segments = [
            $this->b64(json_encode(['typ' => 'JWT', 'alg' => 'HS256'])),
            $this->b64(json_encode($payload)),
        ];
        $segments[] = $this->sign(implode('.', $segments));
        return implode('.', $segments);
    }

    /** @return array decoded claims. Throws on invalid/expired token. */
    public function verify(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) throw new \InvalidArgumentException('Malformed token.');
        [$h, $p, $sig] = $parts;
        if (!hash_equals($this->sign("$h.$p"), $sig)) throw new \RuntimeException('Invalid signature.');
        $claims = json_decode($this->b64decode($p), true);
        if (!is_array($claims)) throw new \RuntimeException('Invalid payload.');
        if (($claims['exp'] ?? 0) < time()) throw new \RuntimeException('Token expired.');
        return $claims;
    }

    private function sign(string $data): string { return $this->b64(hash_hmac('sha256', $data, $this->secret, true)); }
    private function b64(string $d): string { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
    private function b64decode(string $d): string { return base64_decode(strtr($d, '-_', '+/')) ?: ''; }
}
