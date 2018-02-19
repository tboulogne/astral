<?php

namespace Tests\Feature;

use JWTAuth;
use Mockery as m;
use Tests\TestCase;
use Astral\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticatesUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    /** @test */
    public function it_redirects_to_github()
    {
        $response = $this->get('/auth/github');

        $this->assertContains('github.com/login/oauth', $response->getTargetUrl());
    }

    /** @test */
    public function it_retrieves_github_request_and_creates_a_new_user()
    {
        $this->mockSocialiteFacade();

        JWTAuth::shouldReceive('fromUser')->withAnyArgs()->andReturn('12345');

        $response = $this->get('/auth/github/callback');

        $user = User::first();
        $token = JWTAuth::fromUser($user);
        $expiry = auth()->factory()->getTTL() * 60;

        $githubUser = Socialite::driver('github')->user();

        $this->assertEquals($githubUser->getNickname(), $user->username);

        $response->assertRedirect("/auth?token={$token}&token_expiry={$expiry}");
    }


    public function mockSocialiteFacade()
    {
        $abstractUser = m::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getId')
            ->andReturn(1234567890)
            ->shouldReceive('getNickname')
            ->andReturn('JaneDoe')
            ->shouldReceive('getName')
            ->andReturn('Jane Doe')
            ->shouldReceive('getAvatar')
            ->andReturn('https://en.gravatar.com/userimage');
        $abstractUser->token = 'abcde12345';

        $provider = m::mock('Laravel\Socialite\Contracts\Provider');
        $provider->shouldReceive('user')->andReturn($abstractUser);

        Socialite::shouldReceive('driver')->with('github')->andReturn($provider);
    }
}