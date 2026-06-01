<?php

declare(strict_types=1);

namespace TexHub\SocialAuth\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * Laravel facade for the Social Auth manager.
 *
 * @method static \TexHub\SocialAuth\Contracts\Provider driver(string $name)
 * @method static \TexHub\SocialAuth\SocialAuth          configure(string $name, \TexHub\SocialAuth\ProviderConfig $config)
 * @method static \TexHub\SocialAuth\SocialAuth          extend(string $name, string $providerClass)
 * @method static array                                  configured()
 *
 * @see \TexHub\SocialAuth\SocialAuth
 */
class SocialAuth extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'social-auth';
    }
}
