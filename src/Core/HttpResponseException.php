<?php
declare(strict_types=1);
namespace App\Core;

/** Thrown (in testing mode) instead of exit() so controller output can be asserted. */
final class HttpResponseException extends \RuntimeException
{
    public function __construct(
        public int $status,
        public string $body,
        public string $type = 'html',
        public ?string $location = null,
    ) {
        parent::__construct("HTTP {$status}");
    }
}
