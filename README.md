# TexHub · Social Auth

**🌐 English** · [Русский](README.ru.md)

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

Simple, elegant **social OAuth 2.0 login** for any PHP framework — **Google** & **GitHub** out of the box, easily extensible — with first-class **Laravel** support.

---

## ✨ Features

- 🔐 **OAuth 2.0 Authorization Code** flow done for you
- 🟢 **Google** & 🐙 **GitHub** providers built in (GitHub falls back to the verified primary email)
- 👤 **Normalized `User`** — `id`, `nickname`, `name`, `email`, `avatar`, `token`, `raw`
- 🛡 **CSRF state** helper, custom scopes & extra params
- 🧩 **Extensible** — add your own provider in a few lines
- 🧪 Fully unit-tested (no network), pluggable HTTP transport

---

## 📦 Installation

```bash
composer require texhub/social-auth
```

Requirements: **PHP ≥ 8.2** with `curl`, `json`.

---

## 🚀 Quick start

```php
use TexHub\SocialAuth\SocialAuth;

$social = SocialAuth::fromArray([
    'google' => [
        'client_id' => '...', 'client_secret' => '...',
        'redirect' => 'https://app.tj/auth/google/callback',
    ],
    'github' => [
        'client_id' => '...', 'client_secret' => '...',
        'redirect' => 'https://app.tj/auth/github/callback',
    ],
]);

// 1) Send the user to the provider (store the state in the session for CSRF):
$state = SocialAuth::generateState();
$_SESSION['oauth_state'] = $state;
header('Location: ' . $social->driver('google')->redirectUrl($state));

// 2) On your callback, verify state then get the user:
if (($_GET['state'] ?? null) !== ($_SESSION['oauth_state'] ?? null)) {
    exit('Invalid state');
}
$user = $social->driver('google')->userFromCode($_GET['code']);

$user->id;            // provider user id
$user->name;          // full name
$user->email;         // email
$user->avatar;        // avatar URL
$user->nickname;      // login/handle (GitHub)
$user->token->accessToken;   // OAuth access token
$user->token->refreshToken;  // when available (Google offline access)
```

---

## 🟢 Google / 🐙 GitHub

Both work identically — just switch the driver name:

```php
$social->driver('github')->redirectUrl($state);
$user = $social->driver('github')->userFromCode($code);
```

Default scopes: Google → `openid profile email`, GitHub → `read:user user:email`.
Override per provider via `scopes`, and add provider params via `extra`
(e.g. Google `['access_type' => 'offline', 'prompt' => 'consent']` for a refresh token).

---

## 🧩 Add your own provider

```php
use TexHub\SocialAuth\Providers\AbstractProvider;
use TexHub\SocialAuth\User;

final class FacebookProvider extends AbstractProvider
{
    protected function authorizeUrl(): string { return 'https://www.facebook.com/v19.0/dialog/oauth'; }
    protected function tokenUrl(): string     { return 'https://graph.facebook.com/v19.0/oauth/access_token'; }
    protected function defaultScopes(): array { return ['email', 'public_profile']; }
    protected function fetchUser(string $token): array {
        return $this->get('https://graph.facebook.com/me?fields=id,name,email,picture', $token);
    }
    protected function mapUser(array $raw): User {
        return new User((string) $raw['id'], null, $raw['name'] ?? null, $raw['email'] ?? null,
            $raw['picture']['data']['url'] ?? null, $raw);
    }
}

$social->extend('facebook', FacebookProvider::class)
       ->configure('facebook', \TexHub\SocialAuth\ProviderConfig::fromArray($cfg));
```

---

## ⚙️ Error handling

```php
use TexHub\SocialAuth\Exceptions\ApiException;
use TexHub\SocialAuth\Exceptions\ConfigurationException;

try {
    $user = $social->driver('google')->userFromCode($code);
} catch (ApiException $e) {
    // token exchange / profile error — $e->getMessage(), $e->httpStatus, $e->payload
} catch (ConfigurationException $e) {
    // unknown / unconfigured provider
}
```

---

## <a name="laravel"></a>🧩 Laravel

Auto-discovered. Publish config:

```bash
php artisan vendor:publish --tag=social-auth-config
```

`.env`:

```dotenv
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://app.tj/auth/google/callback

GITHUB_CLIENT_ID=...
GITHUB_CLIENT_SECRET=...
GITHUB_REDIRECT_URI=https://app.tj/auth/github/callback
```

Controller:

```php
use Illuminate\Http\Request;
use TexHub\SocialAuth\Laravel\SocialAuth;

public function redirect(string $provider, Request $request)
{
    $state = \TexHub\SocialAuth\SocialAuth::generateState();
    $request->session()->put('oauth_state', $state);

    return redirect()->away(SocialAuth::driver($provider)->redirectUrl($state));
}

public function callback(string $provider, Request $request)
{
    abort_unless($request->query('state') === $request->session()->pull('oauth_state'), 419);

    $user = SocialAuth::driver($provider)->userFromCode($request->query('code'));

    $account = User::updateOrCreate(
        ['provider' => $provider, 'provider_id' => $user->id],
        ['name' => $user->name, 'email' => $user->email, 'avatar' => $user->avatar],
    );
    auth()->login($account);

    return redirect('/dashboard');
}
```

Routes:

```php
Route::get('/auth/{provider}', [AuthController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [AuthController::class, 'callback']);
```

---

## 🧪 Testing

Inject a fake transport — no network needed:

```php
use TexHub\SocialAuth\SocialAuth;
use TexHub\SocialAuth\Tests\Support\FakeTransport;

$t = (new FakeTransport())
    ->on('oauth2.googleapis.com/token', ['access_token' => 't', 'token_type' => 'Bearer'])
    ->on('userinfo', ['sub' => '1', 'email' => 'a@b.c', 'name' => 'A']);

$social = SocialAuth::fromArray($configs, $t);
$user = $social->driver('google')->userFromCode('code');
```

```bash
composer install && composer test
```

---

## 📚 Architecture

```
src/
├── SocialAuth.php           # manager / driver factory
├── ProviderConfig.php       # per-provider client id/secret/redirect/scopes
├── Token.php · User.php     # normalized value objects
├── Contracts/Provider.php   # provider interface
├── Providers/               # AbstractProvider, GoogleProvider, GitHubProvider
├── Http/                    # Transport, CurlTransport, RawResponse
├── Exceptions/              # ApiException, ConfigurationException, …
└── Laravel/                 # ServiceProvider + Facade
```

---

## License

MIT © TexHub Pro — built by Mahmudi Shodmehr.
