<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserLoginTrackingService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset / first-time set view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', [
            'request' => $request,
            'isInitialPasswordSetup' => $this->isInitialPasswordSetup($request),
            'redirectAfterSetup' => $this->safeRedirectPath((string) old('redirect', $request->query('redirect', ''))),
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'intent' => ['nullable', 'in:set,reset'],
            'redirect' => ['nullable', 'string', 'max:2048'],
        ]);

        $isInitialPasswordSetup = $this->isInitialPasswordSetup($request);
        $resetUser = null;

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request, &$resetUser) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
                $resetUser = $user;
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            $message = $isInitialPasswordSetup
                ? __('passwords.set')
                : __($status);

            if ($isInitialPasswordSetup && $resetUser instanceof User) {
                Auth::login($resetUser);
                $request->session()->regenerate();
                app(UserLoginTrackingService::class)->recordAuthenticatedSession($resetUser);
                $request->session()->put('pnedu_login_session_recorded', true);

                return redirect()
                    ->to($this->safeRedirectPath((string) $request->input('redirect', '')) ?? route('dashboard.szkolenia'))
                    ->with('status', $message);
            }

            return redirect()->route('login')->with('status', $message);
        }

        $error = $status === Password::INVALID_TOKEN && $isInitialPasswordSetup
            ? __('passwords.token_set')
            : __($status);

        return back()->withInput($request->only('email', 'intent', 'redirect'))
            ->withErrors(['email' => $error]);
    }

    /**
     * First-time password (konto z provision ADM) vs klasyczny reset.
     *
     * Stare maile nadal wskazują /reset-password/… — rozpoznajemy je po braku logowania.
     */
    private function isInitialPasswordSetup(Request $request): bool
    {
        $intent = $request->input('intent', $request->query('intent'));
        if ($intent === 'set' || $request->routeIs('password.set')) {
            return true;
        }

        $email = User::normalizeEmail((string) $request->input('email', $request->query('email')));
        if ($email === null) {
            return false;
        }

        $user = User::query()->where('email', $email)->first();

        return $user !== null
            && $user->last_login_at === null
            && (int) ($user->login_count ?? 0) === 0;
    }

    private function safeRedirectPath(string $redirect): ?string
    {
        $redirect = trim($redirect);
        if ($redirect === '') {
            return null;
        }

        if (! str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            return null;
        }

        return $redirect;
    }
}
