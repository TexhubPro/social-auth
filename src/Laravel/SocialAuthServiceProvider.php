<?php

declare(strict_types=1);

namespace TexHub\SocialAuth\Laravel;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use TexHub\SocialAuth\SocialAuth as SocialAuthManager;

class SocialAuthServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/social-auth.php', 'social-auth');

        $this->app->singleton(SocialAuthManager::class, function ($app): SocialAuthManager {
            return SocialAuthManager::fromArray((array) $app['config']->get('social-auth', []));
        });

        $this->app->alias(SocialAuthManager::class, 'social-auth');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/social-auth.php' => $this->app->configPath('social-auth.php'),
            ], 'social-auth-config');
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [SocialAuthManager::class, 'social-auth'];
    }
}
