<?php

declare(strict_types=1);

namespace TexHub\SocialAuth\Providers;

use TexHub\SocialAuth\Contracts\Provider;
use TexHub\SocialAuth\Exceptions\ApiException;
use TexHub\SocialAuth\Http\CurlTransport;
use TexHub\SocialAuth\Http\RawResponse;
use TexHub\SocialAuth\Http\Transport;
use TexHub\SocialAuth\ProviderConfig;
use TexHub\SocialAuth\Token;
use TexHub\SocialAuth\User;

/**
 * Shared OAuth 2.0 Authorization Code flow. Concrete providers only supply
 * their endpoints, default scopes and user mapping.
 */
abstract class AbstractProvider implements Provider
{
    protected string $scopeSeparator = ' ';

    public function __construct(
        protected readonly ProviderConfig $config,
        protected readonly Transport $transport = new CurlTransport(),
    ) {
    }

    abstract protected function authorizeUrl(): string;

    abstract protected function tokenUrl(): string;

    /**
     * @return array<int, string>
     */
    abstract protected function defaultScopes(): array;

    /**
     * Fetch the raw user payload using the access token.
     *
     * @return array<string, mixed>
     */
    abstract protected function fetchUser(string $accessToken): array;

    /**
     * Map a raw user payload to a normalized {@see User}.
     *
     * @param array<string, mixed> $raw
     */
    abstract protected function mapUser(array $raw): User;

    /**
     * Generate a random anti-CSRF state value. Store it (e.g. in session) and
     * compare it against the `state` returned to your callback.
     */
    public static function generateState(): string
    {
        return bin2hex(random_bytes(20));
    }

    public function redirectUrl(?string $state = null): string
    {
        $params = [
            'client_id' => $this->config->clientId,
            'redirect_uri' => $this->config->redirectUri,
            'scope' => implode($this->scopeSeparator, $this->scopes()),
            'response_type' => 'code',
        ] + $this->config->extra;

        if ($state !== null) {
            $params['state'] = $state;
        }

        return $this->authorizeUrl() . '?' . http_build_query($params);
    }

    public function getAccessToken(string $code): Token
    {
        $response = $this->transport->request('POST', $this->tokenUrl(), [
            'Accept' => 'application/json',
        ], [
            'grant_type' => 'authorization_code',
            'client_id' => $this->config->clientId,
            'client_secret' => $this->config->clientSecret,
            'redirect_uri' => $this->config->redirectUri,
            'code' => $code,
        ]);

        $data = $this->decode($response);

        if (empty($data['access_token'])) {
            throw new ApiException(
                'Token exchange failed: ' . ($data['error_description'] ?? $data['error'] ?? 'no access_token returned'),
                $response->statusCode,
                $data,
            );
        }

        return Token::fromArray($data);
    }

    public function userFromToken(string $accessToken): User
    {
        return $this->mapUser($this->fetchUser($accessToken));
    }

    public function userFromCode(string $code): User
    {
        $token = $this->getAccessToken($code);
        $user = $this->userFromToken($token->accessToken);

        return new User(
            id: $user->id,
            nickname: $user->nickname,
            name: $user->name,
            email: $user->email,
            avatar: $user->avatar,
            raw: $user->raw,
            token: $token,
        );
    }

    /**
     * @return array<int, string>
     */
    protected function scopes(): array
    {
        return $this->config->scopes !== [] ? $this->config->scopes : $this->defaultScopes();
    }

    /**
     * GET a URL with a Bearer token and decode the JSON response.
     *
     * @param array<string, string> $headers
     *
     * @return array<string, mixed>
     */
    protected function get(string $url, string $accessToken, array $headers = []): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/json',
        ] + $headers;

        return $this->decode($this->transport->request('GET', $url, $headers));
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(RawResponse $response): array
    {
        $data = json_decode($response->body, true);

        if (! is_array($data)) {
            throw new ApiException('Unexpected non-JSON response from provider.', $response->statusCode);
        }

        if (! $response->isSuccessful() && ! isset($data['access_token'])) {
            $message = $data['error_description'] ?? $data['error'] ?? ($data['message'] ?? 'OAuth provider error');
            throw new ApiException((string) $message, $response->statusCode, $data);
        }

        return $data;
    }
}
