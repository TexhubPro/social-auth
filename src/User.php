<?php

declare(strict_types=1);

namespace TexHub\SocialAuth;

/**
 * A normalized social user profile, consistent across providers.
 */
final class User
{
    /**
     * @param array<string, mixed> $raw The provider's raw user payload.
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $nickname = null,
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $avatar = null,
        public readonly array $raw = [],
        public readonly ?Token $token = null,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->raw[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nickname' => $this->nickname,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
        ];
    }
}
