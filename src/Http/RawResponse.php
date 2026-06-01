<?php

declare(strict_types=1);

namespace TexHub\SocialAuth\Http;

/**
 * Raw transport result: HTTP status code and the undecoded response body.
 */
final class RawResponse
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
