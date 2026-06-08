<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SessionExpiredHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_mismatch_on_login_redirects_back_with_friendly_message(): void
    {
        $request = Request::create('/login', 'POST', [
            'email' => 'user@example.com',
            'password' => 'secret',
        ]);
        $request->setLaravelSession($this->app['session.store']);
        $request->session()->start();

        $response = app(ExceptionHandler::class)->render($request, new HttpException(419));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', parse_url($response->getTargetUrl(), PHP_URL_PATH));
        $this->assertSame(
            'Sesja wygasła — spróbuj ponownie.',
            $request->session()->get('error')
        );
        $this->assertSame('user@example.com', $request->session()->getOldInput('email'));
    }

    public function test_token_mismatch_for_authenticated_user_redirects_to_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $request = Request::create('/profile', 'PATCH', [
            'first_name' => 'Jan',
        ]);
        $request->setLaravelSession($this->app['session.store']);

        $response = app(ExceptionHandler::class)->render($request, new HttpException(419));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login'), $response->getTargetUrl());
        $this->assertSame(
            'Sesja wygasła — spróbuj ponownie.',
            $request->session()->get('error')
        );
        $this->assertGuest();
    }

    public function test_token_mismatch_for_json_request_returns_friendly_message(): void
    {
        $request = Request::create('/login', 'POST', [
            'email' => 'user@example.com',
            'password' => 'secret',
        ]);
        $request->headers->set('Accept', 'application/json');

        $response = app(ExceptionHandler::class)->render($request, new HttpException(419));

        $this->assertSame(419, $response->getStatusCode());
        $this->assertSame(
            ['message' => 'Sesja wygasła — spróbuj ponownie.'],
            json_decode($response->getContent(), true)
        );
    }
}
