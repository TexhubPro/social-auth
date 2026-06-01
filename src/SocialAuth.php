<?php

declare(strict_types=1);

namespace TexHub\SocialAuth;

use TexHub\SocialAuth\Contracts\Provider;
use TexHub\SocialAuth\Exceptions\ConfigurationException;
use TexHub\SocialAuth\Http\CurlTransport;
use TexHub\SocialAuth\Http\Transport;
use TexHub\SocialAuth\Providers\AbstractProvider;
use TexHub\SocialAuth\Providers\GitHubProvider;
use TexHub\SocialAuth\Providers\GoogleProvider;

/**
 * Entry point / driver factory for social OAuth providers.
 *
 * ```php
 * $social = SocialAuth::fromArray([
 *     'google' => ['client_id' => '...', 'client_secret' => '...', 'redirect' => 'https://app/callback/google'],
 *     'github' => ['client_id' => '...', 'client_secret' => '...', 'redirect' => 'https://app/callback/github'],
 * ]);
 *
 * $state = SocialAuth::generateState();              // store in session
 * $url   = $social->driver('google')->redirectUrl($state);
 * // ...on callback:
 * $user  = $social->driver('google')->userFromCode($code);
 * ```
 */
final class SocialAuth
{
    /** @var array<string, class-string<AbstractProvider>> */
    private array $providers = [
        GoogleProvider::NAME => GoogleProvider::class,
        GitHubProvider::NAME => GitHubProvider::class,
    ];

    /** @var array<string, ProviderConfig> */
    private array $configs = [];

    /** @var array<string, Provider> */
    private array $resolved = [];

    public function __construct(
        private readonly Transport $transport = new CurlTransport(),
    ) {
    }

    /**
     * Build from a map of provider configs.
     *
     * @param array<string, array<string, mixed>> $configs
     */
    public static function fromArray(array $configs, ?Transport $transport = null): self
    {
        $instance = new self($transport ?? new CurlTransport());

        foreach ($configs as $name => $config) {
            if (is_array($config) && ! empty($config['client_id'])) {
                $instance->configs[$name] = ProviderConfig::fromArray($config);
            }
        }

        return $instance;
    }

    /**
     * Register the config for a single provider.
     */
    public function configure(string $name, ProviderConfig $config): self
    {
        $this->configs[$name] = $config;
        unset($this->resolved[$name]);

        return $this;
    }

    /**
     * Register a custom provider class (must extend AbstractProvider).
     *
     * @param class-string<AbstractProvider> $providerClass
     */
    public function extend(string $name, string $providerClass): self
    {
        $this->providers[$name] = $providerClass;

        return $this;
    }

    /**
     * Resolve a provider driver by name (e.g. "google", "github").
     */
    public function driver(string $name): Provider
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        if (! isset($this->providers[$name])) {
            throw new ConfigurationException(sprintf('Unknown social provider "%s".', $name));
        }

        if (! isset($this->configs[$name])) {
            throw new ConfigurationException(sprintf('Social provider "%s" is not configured.', $name));
        }

        $class = $this->providers[$name];

        return $this->resolved[$name] = new $class($this->configs[$name], $this->transport);
    }

    /**
     * @return array<int, string>
     */
    public function configured(): array
    {
        return array_keys($this->configs);
    }

    /**
     * Generate a random anti-CSRF state value to store in the session.
     */
    public static function generateState(): string
    {
        return AbstractProvider::generateState();
    }
}
