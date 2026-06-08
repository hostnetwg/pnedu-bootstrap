<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Potwierdza adres e-mail na podstawie linku z wiadomości (bez limitu czasu podpisu URL).
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = $request->user();

        if (! hash_equals((string) $id, (string) $user->getKey())) {
            $targetUser = User::query()->find($id);

            if (
                $targetUser !== null
                && hash_equals((string) $hash, sha1($targetUser->getEmailForVerification()))
            ) {
                return $this->redirectWrongAccountToLogin($request, $targetUser);
            }

            return $this->verificationFailed(
                'Ten link weryfikacyjny dotyczy innego konta. Wyloguj się i zaloguj na adres e-mail, na który przyszła wiadomość.'
            );
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->verificationFailed(
                'Link weryfikacyjny jest nieprawidłowy lub Twój adres e-mail został zmieniony. Wyślij nowy link weryfikacyjny ze strony poniżej.'
            );
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }

    private function redirectWrongAccountToLogin(Request $request, User $targetUser): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->put('url.intended', $request->fullUrl());
        $request->session()->flash('email_verification_relogin', true);
        $request->session()->flash('login_email_hint', $targetUser->getEmailForVerification());

        return redirect()->route('login');
    }

    private function verificationFailed(string $message): RedirectResponse
    {
        return redirect()
            ->route('verification.notice')
            ->with('error', $message);
    }
}
