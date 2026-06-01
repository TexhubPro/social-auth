<?php

declare(strict_types=1);

namespace TexHub\SocialAuth;

/**
 * An OAuth access token with its metadata.
 */
final class Token
{
    /**
     * @param array<int, string>   $scopes
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly string $accessToken,
        public readonly ?string $refreshToken = null,
        public readonly ?int $expiresIn = null,
        public readonly string $tokenType = 'Bearer',
        public readonly array $scopes = [],
        public readonly array $raw = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $scope = $data['scope'] ?? '';
        $scopes = is_array($scope)
            ? $scope
            : array_values(array_filter(preg_split('/[\s,]+/', (string) $scope) ?: []));

        return new self(
            accessToken: (string) ($data['access_token'] ?? ''),
            refreshToken: isset($data['refresh_token']) ? (string) $data['refresh_token'] : null,
            expiresIn: isset($data['expires_in']) ? (int) $data['expires_in'] : null,
            tokenType: (string) ($data['token_type'] ?? 'Bearer'),
            scopes: $scopes,
            raw: $data,
        );
    }

    public function __toString(): string
    {
        return $this->accessToken;
    }
}
