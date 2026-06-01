<?php

declare(strict_types=1);

namespace TexHub\SocialAuth\Tests\Feature;

use PHPUnit\Framework\TestCase;
use TexHub\SocialAuth\Exceptions\ApiException;
use TexHub\SocialAuth\Exceptions\ConfigurationException;
use TexHub\SocialAuth\SocialAuth;
use TexHub\SocialAuth\Tests\Support\FakeTransport;

final class OAuthTest extends TestCase
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private function configs(): array
    {
        return [
            'google' => ['client_id' => 'G_ID', 'client_secret' => 'G_SECRET', 'redirect' => 'https://app.tj/cb/google'],
            'github' => ['client_id' => 'H_ID', 'client_secret' => 'H_SECRET', 'redirect' => 'https://app.tj/cb/github'],
        ];
    }

    public function test_google_redirect_url(): void
    {
        $social = SocialAuth::fromArray($this->configs(), new FakeTransport());

        $url = $social->driver('google')->redirectUrl('state123');

        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);
        $this->assertStringContainsString('client_id=G_ID', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('scope=openid+profile+email', $url);
        $this->assertStringContainsString('state=state123', $url);
        $this->assertStringContainsString('redirect_uri=https%3A%2F%2Fapp.tj%2Fcb%2Fgoogle', $url);
    }

    public function test_google_user_from_code(): void
    {
        $t = (new FakeTransport())
            ->on('oauth2.googleapis.com/token', ['access_token' => 'ya29.token', 'expires_in' => 3599, 'token_type' => 'Bearer', 'scope' => 'openid email profile'])
            ->on('openidconnect.googleapis.com/v1/userinfo', [
                'sub' => '11223344', 'name' => 'Ali Valiev', 'email' => 'ali@example.com',
                'email_verified' => true, 'picture' => 'https://lh3.google/a.png',
            ]);

        $user = SocialAuth::fromArray($this->configs(), $t)->driver('google')->userFromCode('AUTHCODE');

        $this->assertSame('11223344', $user->id);
        $this->assertSame('Ali Valiev', $user->name);
        $this->assertSame('ali@example.com', $user->email);
        $this->assertSame('https://lh3.google/a.png', $user->avatar);
        $this->assertSame('ya29.token', $user->token->accessToken);

        // token exchange was a POST with the auth code
        $tokenReq = $t->requestTo('oauth2.googleapis.com/token');
        $this->assertSame('POST', $tokenReq['method']);
        $this->assertSame('AUTHCODE', $tokenReq['form']['code']);
        $this->assertSame('authorization_code', $tokenReq['form']['grant_type']);
    }

    public function test_github_user_with_private_email_fallback(): void
    {
        $t = (new FakeTransport())
            ->on('github.com/login/oauth/access_token', ['access_token' => 'gho_token', 'token_type' => 'bearer', 'scope' => 'read:user,user:email'])
            ->on('api.github.com/user/emails', [
                ['email' => 'secondary@example.com', 'primary' => false, 'verified' => true],
                ['email' => 'primary@example.com', 'primary' => true, 'verified' => true],
            ])
            ->on('api.github.com/user', ['id' => 42, 'login' => 'ali', 'name' => 'Ali', 'email' => null, 'avatar_url' => 'https://gh/a.png']);

        $user = SocialAuth::fromArray($this->configs(), $t)->driver('github')->userFromCode('CODE');

        $this->assertSame('42', $user->id);
        $this->assertSame('ali', $user->nickname);
        $this->assertSame('primary@example.com', $user->email); // fetched from /user/emails
        $this->assertSame('https://gh/a.png', $user->avatar);

        // GitHub token request asked for JSON
        $this->assertSame('application/json', $t->requestTo('access_token')['headers']['Accept']);
    }

    public function test_unknown_or_unconfigured_driver_throws(): void
    {
        $social = SocialAuth::fromArray($this->configs(), new FakeTransport());

        $this->expectException(ConfigurationException::class);
        $social->driver('facebook');
    }

    public function test_token_exchange_error_is_raised(): void
    {
        $t = (new FakeTransport())->on('oauth2.googleapis.com/token', ['error' => 'invalid_grant', 'error_description' => 'Bad code'], 400);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Bad code');

        SocialAuth::fromArray($this->configs(), $t)->driver('google')->getAccessToken('BAD');
    }

    public function test_generate_state_is_random_hex(): void
    {
        $a = SocialAuth::generateState();
        $b = SocialAuth::generateState();

        $this->assertNotSame($a, $b);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $a);
    }
}
