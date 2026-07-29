<?php

namespace App\Http\Controllers;

use App\Mail\TrainingOfferInquiryMail;
use App\Models\TrainingOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class TrainingOfferInquiryController extends Controller
{
    /** Formularz ogólnego zapytania bez powiązania z konkretną ofertą. */
    public function create()
    {
        return view('training-offers.pedagogical-councils.general-inquiry');
    }

    /** Wysyłka ogólnego zapytania — bez oferty, temat wpisuje sam użytkownik. */
    public function storeGeneral(Request $request): RedirectResponse
    {
        $data = $this->validateInquiry($request);

        $data['offer_title'] = filled($data['offer_topic'] ?? null)
            ? $data['offer_topic']
            : '(temat nie określony)';
        $data['offer_url'] = null;

        Mail::to(config('mail.system.reply_to_address'))
            ->send(new TrainingOfferInquiryMail($data));

        return redirect()
            ->route('training-offers.pedagogical-councils.inquiry.general')
            ->with('success', 'Dziękujemy za wiadomość. Odezwiemy się, aby omówić możliwości szkolenia.');
    }

    /** Wysyłka zapytania dotyczącego konkretnej oferty. */
    public function store(Request $request, string $slug): RedirectResponse
    {
        $offer = TrainingOffer::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        $data = $this->validateInquiry($request);

        $data['offer_title'] = $offer->title;
        $data['offer_url'] = route('training-offers.pedagogical-councils.show', $offer->slug);

        Mail::to(config('mail.system.reply_to_address'))
            ->send(new TrainingOfferInquiryMail($data));

        return redirect()
            ->route('training-offers.pedagogical-councils.show', $offer->slug)
            ->with('success', 'Dziękujemy za zapytanie. Skontaktujemy się, aby omówić szczegóły szkolenia.');
    }

    private function validateInquiry(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'institution' => 'nullable|string|max:255',
            'offer_topic' => 'nullable|string|max:500',
            'preferred_format' => ['nullable', Rule::in(['online', 'onsite', 'to_discuss'])],
            'message' => 'required|string|max:3000',
            'consent' => 'accepted',
        ], [
            'name.required' => 'Podaj imię i nazwisko.',
            'email.required' => 'Podaj adres e-mail.',
            'email.email' => 'Podaj prawidłowy adres e-mail.',
            'message.required' => 'Napisz krótką wiadomość.',
            'consent.accepted' => 'Zgoda na przetwarzanie danych jest wymagana.',
        ]);
    }
}
