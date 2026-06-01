# TexHub · Social Auth

[English](README.md) · **🌐 Русский**

[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.2-777bb4.svg)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-11%20%7C%2012%20%7C%2013-ff2d20.svg)](#laravel)

Простой и аккуратный **вход через соцсети по OAuth 2.0** для любого PHP-фреймворка — **Google** и **GitHub** из коробки, легко расширяется — с полной поддержкой **Laravel**.

---

## ✨ Возможности

- 🔐 Полный поток **OAuth 2.0 Authorization Code** «под капотом»
- 🟢 **Google** и 🐙 **GitHub** встроены (GitHub берёт подтверждённый основной email)
- 👤 **Нормализованный `User`** — `id`, `nickname`, `name`, `email`, `avatar`, `token`, `raw`
- 🛡 Хелпер **CSRF-state**, кастомные scope и доп. параметры
- 🧩 **Расширяемость** — свой провайдер в несколько строк
- 🧪 Полностью покрыт тестами (без сети), подменяемый HTTP-транспорт

---

## 📦 Установка

```bash
composer require texhub/social-auth
```

Требования: **PHP ≥ 8.2** с `curl`, `json`.

---

## 🚀 Быстрый старт

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

// 1) Отправляем пользователя к провайдеру (state храним в сессии для CSRF):
$state = SocialAuth::generateState();
$_SESSION['oauth_state'] = $state;
header('Location: ' . $social->driver('google')->redirectUrl($state));

// 2) В callback сверяем state и получаем пользователя:
if (($_GET['state'] ?? null) !== ($_SESSION['oauth_state'] ?? null)) {
    exit('Invalid state');
}
$user = $social->driver('google')->userFromCode($_GET['code']);

$user->id;            // id пользователя у провайдера
$user->name;          // имя
$user->email;         // email
$user->avatar;        // URL аватара
$user->nickname;      // логин (GitHub)
$user->token->accessToken;   // OAuth access token
$user->token->refreshToken;  // если есть (offline-доступ Google)
```

---

## 🟢 Google / 🐙 GitHub

Работают одинаково — просто меняете имя драйвера:

```php
$social->driver('github')->redirectUrl($state);
$user = $social->driver('github')->userFromCode($code);
```

Скоупы по умолчанию: Google → `openid profile email`, GitHub → `read:user user:email`.
Переопределяются через `scopes`, доп. параметры — через `extra`
(например, Google `['access_type' => 'offline', 'prompt' => 'consent']` для refresh-токена).

---

## 🧩 Свой провайдер

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

## ⚙️ Обработка ошибок

```php
use TexHub\SocialAuth\Exceptions\ApiException;
use TexHub\SocialAuth\Exceptions\ConfigurationException;

try {
    $user = $social->driver('google')->userFromCode($code);
} catch (ApiException $e) {
    // ошибка обмена кода / получения профиля — $e->getMessage(), $e->httpStatus, $e->payload
} catch (ConfigurationException $e) {
    // неизвестный / ненастроенный провайдер
}
```

---

## <a name="laravel"></a>🧩 Laravel

Регистрируется автоматически. Опубликуйте конфиг:

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

Контроллер:

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

Маршруты:

```php
Route::get('/auth/{provider}', [AuthController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [AuthController::class, 'callback']);
```

---

## 🧪 Тестирование

Подставьте фейковый транспорт — сеть не нужна:

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

## 📚 Архитектура

```
src/
├── SocialAuth.php           # менеджер / фабрика драйверов
├── ProviderConfig.php       # client id/secret/redirect/scopes для провайдера
├── Token.php · User.php     # нормализованные value-объекты
├── Contracts/Provider.php   # интерфейс провайдера
├── Providers/               # AbstractProvider, GoogleProvider, GitHubProvider
├── Http/                    # Transport, CurlTransport, RawResponse
├── Exceptions/              # ApiException, ConfigurationException, …
└── Laravel/                 # ServiceProvider + Facade
```

---

## Лицензия

MIT © TexHub Pro — разработано Mahmudi Shodmehr.
