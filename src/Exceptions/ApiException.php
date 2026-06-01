<?php

declare(strict_types=1);

namespace TexHub\SocialAuth\Exceptions;

/**
 * Thrown when an OAuth provider returns an error during token exchange or
 * profile retrieval.
 */
class ApiException extends SocialAuthException
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly array $payload = [],
    ) {
        parent::__construct($message, $httpStatus);
    }
}
