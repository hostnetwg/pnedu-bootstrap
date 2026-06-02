<?php

namespace App\Http\Controllers;

use App\Services\SendyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'newsletter_consent' => ['required', 'accepted'],
        ], [
            'email.required' => 'Podaj adres e-mail.',
            'email.email' => 'Podaj prawidłowy adres e-mail.',
            'newsletter_consent.accepted' => 'Musisz wyrazić zgodę na otrzymywanie newslettera.',
        ]);

        $email = strtolower(trim($validated['email']));

        $sendy = SendyService::fromConfig();
        if ($sendy === null) {
            Log::warning('Newsletter: Sendy not configured (missing SENDY_URL or SENDY_API_KEY)');

            return redirect()
                ->to(route('home').'#newsletter')
                ->with('error', 'Zapis na newsletter jest tymczasowo niedostępny. Spróbuj później.');
        }

        $subscribed = $sendy->subscribeHomepageNewsletter($email);

        if (! $subscribed) {
            return redirect()
                ->to(route('home').'#newsletter')
                ->withInput()
                ->with('error', 'Nie udało się zapisać na newsletter. Spróbuj ponownie za chwilę.');
        }

        return redirect()
            ->to(route('home').'#newsletter')
            ->with('success', 'Dziękujemy! Zapisaliśmy Twój adres e-mail na listę newslettera.');
    }
}
