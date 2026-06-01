<?php

declare(strict_types=1);

namespace TexHub\SocialAuth\Providers;

use TexHub\SocialAuth\User;

/**
 * Google OAuth 2.0 / OpenID Connect provider.
 *
 * @see https://developers.google.com/identity/protocols/oauth2
 */
final class GoogleProvider extends AbstractProvider
{
    public const NAME = 'google';

    protected function authorizeUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    protected function tokenUrl(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    /**
     * @return array<int, string>
     */
    protected function defaultScopes(): array
    {
        return ['openid', 'profile', 'email'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function fetchUser(string $accessToken): array
    {
        return $this->get('https://openidconnect.googleapis.com/v1/userinfo', $accessToken);
    }

    /**
     * @param array<string, mixed> $raw
     */
    protected function mapUser(array $raw): User
    {
        return new User(
            id: (string) ($raw['sub'] ?? ''),
            nickname: null,
            name: isset($raw['name']) ? (string) $raw['name'] : null,
            email: isset($raw['email']) ? (string) $raw['email'] : null,
            avatar: isset($raw['picture']) ? (string) $raw['picture'] : null,
            raw: $raw,
        );
    }
}
