<?php

declare(strict_types=1);

namespace TexHub\SocialAuth\Contracts;

use TexHub\SocialAuth\Token;
use TexHub\SocialAuth\User;

/**
 * Contract implemented by every OAuth provider.
 */
interface Provider
{
    /**
     * Build the authorization URL to redirect the user to.
     */
    public function redirectUrl(?string $state = null): string;

    /**
     * Exchange an authorization code for an access token.
     */
    public function getAccessToken(string $code): Token;

    /**
     * Fetch the user profile for an access token.
     */
    public function userFromToken(string $accessToken): User;

    /**
     * Convenience: exchange the code and fetch the user in one call.
     */
    public function userFromCode(string $code): User;
}
