<?php

declare(strict_types=1);

namespace TexHub\SocialAuth\Http;

use TexHub\SocialAuth\Exceptions\TransportException;

/**
 * HTTP transport abstraction so the SDK has no hard dependency on a specific
 * HTTP client and can be fully unit-tested with a fake.
 */
interface Transport
{
    /**
     * @param array<string, string>     $headers
     * @param array<string, mixed>|null $form Form-encoded body.
     *
     * @throws TransportException
     */
    public function request(string $method, string $url, array $headers = [], ?array $form = null): RawResponse;
}
