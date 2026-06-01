<?php

declare(strict_types=1);

namespace TexHub\SocialAuth;

use TexHub\SocialAuth\Exceptions\ConfigurationException;

/**
 * Immutable per-provider OAuth configuration.
 */
final class ProviderConfig
{
    /**
     * @param array<int, string>   $scopes Override the provider's default scopes.
     * @param array<string, mixed> $extra  Extra authorization parameters (e.g. access_type, prompt).
     */
    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret,
        public readonly string $redirectUri,
        public readonly array $scopes = [],
        public readonly array $extra = [],
    ) {
        if (trim($this->clientId) === '') {
            throw new ConfigurationException('OAuth client id must not be empty.');
        }
        if (trim($this->clientSecret) === '') {
            throw new ConfigurationException('OAuth client secret must not be empty.');
        }
        if (trim($this->redirectUri) === '') {
            throw new ConfigurationException('OAuth redirect URI must not be empty.');
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            clientId: (string) ($config['client_id'] ?? ''),
            clientSecret: (string) ($config['client_secret'] ?? ''),
            redirectUri: (string) ($config['redirect'] ?? $config['redirect_uri'] ?? ''),
            scopes: (array) ($config['scopes'] ?? []),
            extra: (array) ($config['extra'] ?? []),
        );
    }
}
