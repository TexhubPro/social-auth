<?php

declare(strict_types=1);

namespace TexHub\SocialAuth\Providers;

use TexHub\SocialAuth\User;

/**
 * GitHub OAuth 2.0 provider.
 *
 * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps
 */
final class GitHubProvider extends AbstractProvider
{
    public const NAME = 'github';

    protected function authorizeUrl(): string
    {
        return 'https://github.com/login/oauth/authorize';
    }

    protected function tokenUrl(): string
    {
        return 'https://github.com/login/oauth/access_token';
    }

    /**
     * @return array<int, string>
     */
    protected function defaultScopes(): array
    {
        return ['read:user', 'user:email'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function fetchUser(string $accessToken): array
    {
        $user = $this->get('https://api.github.com/user', $accessToken);

        // Email can be null when the user keeps it private; fetch it explicitly.
        if (empty($user['email'])) {
            $email = $this->primaryEmail($accessToken);
            if ($email !== null) {
                $user['email'] = $email;
            }
        }

        return $user;
    }

    private function primaryEmail(string $accessToken): ?string
    {
        try {
            $emails = $this->get('https://api.github.com/user/emails', $accessToken);
        } catch (\Throwable) {
            return null;
        }

        $primary = null;
        foreach ($emails as $entry) {
            if (! is_array($entry) || empty($entry['email'])) {
                continue;
            }
            if (($entry['primary'] ?? false) && ($entry['verified'] ?? false)) {
                return (string) $entry['email'];
            }
            $primary ??= (string) $entry['email'];
        }

        return $primary;
    }

    /**
     * @param array<string, mixed> $raw
     */
    protected function mapUser(array $raw): User
    {
        return new User(
            id: (string) ($raw['id'] ?? ''),
            nickname: isset($raw['login']) ? (string) $raw['login'] : null,
            name: isset($raw['name']) ? (string) $raw['name'] : null,
            email: isset($raw['email']) ? (string) $raw['email'] : null,
            avatar: isset($raw['avatar_url']) ? (string) $raw['avatar_url'] : null,
            raw: $raw,
        );
    }
}
