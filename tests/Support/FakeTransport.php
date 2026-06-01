<?php

declare(strict_types=1);

namespace TexHub\SocialAuth\Tests\Support;

use TexHub\SocialAuth\Http\RawResponse;
use TexHub\SocialAuth\Http\Transport;

/**
 * In-memory transport for tests: returns a canned JSON response per URL
 * substring, and records every request.
 */
final class FakeTransport implements Transport
{
    /** @var array<int, array{method: string, url: string, headers: array, form: ?array}> */
    public array $history = [];

    /** @var array<string, array{status: int, body: string}> */
    private array $routes = [];

    /**
     * Map a URL substring to a JSON payload.
     *
     * @param array<mixed> $payload
     */
    public function on(string $urlContains, array $payload, int $status = 200): self
    {
        $this->routes[$urlContains] = ['status' => $status, 'body' => (string) json_encode($payload)];

        return $this;
    }

    public function request(string $method, string $url, array $headers = [], ?array $form = null): RawResponse
    {
        $this->history[] = compact('method', 'url', 'headers', 'form');

        foreach ($this->routes as $needle => $route) {
            if (str_contains($url, $needle)) {
                return new RawResponse($route['status'], $route['body']);
            }
        }

        return new RawResponse(404, '{"error":"not_stubbed"}');
    }

    public function last(): array
    {
        return $this->history[count($this->history) - 1];
    }

    public function requestTo(string $urlContains): ?array
    {
        foreach ($this->history as $entry) {
            if (str_contains($entry['url'], $urlContains)) {
                return $entry;
            }
        }

        return null;
    }
}
