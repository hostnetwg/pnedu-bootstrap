<?php

namespace App\Http\Controllers;

use App\Mail\OrderNotificationMail;
use App\Models\Course;
use App\Models\CoursePriceVariant;
use App\Models\FormOrder;
use App\Models\Participant;
use App\Models\PaymentDisplayOption;
use App\Services\Analytics\BackendAnalyticsTracker;
use App\Services\FormOrderCheckoutResumeService;
use App\Services\FormOrderOnlineAbandonmentService;
use App\Services\OrderFormParticipantService;
use App\Services\OrderFormRecipientIdentityService;
use App\Services\SendyService;
use App\Support\DeveloperOnlinePaymentTest;
use App\Support\OrderFormVariant;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourseController extends Controller
{
    /**
     * @deprecated Użyj {@see FormOrderCheckoutResumeService::SESSION_KEY}.
     */
    private const SESSION_ONLINE_CHECKOUT_FORM_ORDER_RESUME = FormOrderCheckoutResumeService::LEGACY_ONLINE_SESSION_KEY;

    /**
     * Display a listing of online live courses.
     *
     * @return \Illuminate\View\View
     */
    public function onlineLive(Request $request)
    {
        return view('courses.online-live');
    }

    /**
     * Wyświetl listę bezpłatnych szkoleń (TIK w pracy NAUCZYCIELA).
     *
     * @return \Illuminate\View\View
     */
    public function freeCourses(Request $request)
    {
        try {
            $sort = $request->query('sort', 'desc');
            $sort = in_array($sort, ['asc', 'desc']) ? $sort : 'desc';
            $searchQuery = $request->query('q');

            // Pobierz wszystkie course_id z course_series_course dla serii o id = 1
            $seriesCourseIds = DB::connection('pneadm')
                ->table('course_series_course')
                ->where('course_series_id', 1)
                ->pluck('course_id')
                ->toArray();

            if (empty($seriesCourseIds)) {
                // Jeśli brak kursów w serii, zwróć pustą kolekcję
                $courses = Course::whereIn('id', [0])->paginate(20);
            } else {
                // Pobierz kursy z courses na podstawie course_id z course_series_course
                $coursesQuery = Course::with(['instructor', 'onlineDetail'])
                    ->whereIn('id', $seriesCourseIds)
                    ->where('is_active', true);

                if (! empty($searchQuery)) {
                    $coursesQuery->where(function ($q) use ($searchQuery) {
                        $q->where('title', 'like', '%'.$searchQuery.'%')
                            ->orWhere('description', 'like', '%'.$searchQuery.'%');
                    });
                }

                // Sortowanie według daty rozpoczęcia
                $courses = $coursesQuery
                    ->orderBy('start_date', $sort)
                    ->paginate(20)
                    ->appends([
                        'sort' => $sort,
                        'q' => $searchQuery,
                    ]);
            }

            // Sprawdź uczestnictwo dla zalogowanego użytkownika
            $userEmail = auth()->check() ? auth()->user()->email : null;
            $participantCourseIds = [];
            $participantIdsByCourse = []; // Mapowanie course_id => participant_id

            if ($userEmail) {
                try {
                    $normalizedEmail = strtolower(trim($userEmail));
                    $participants = \App\Models\Participant::query()
                        ->forNormalizedEmail($normalizedEmail)
                        ->select('id', 'course_id')
                        ->get();

                    $participantCourseIds = $participants->pluck('course_id')->toArray();
                    $participantIdsByCourse = $participants->pluck('id', 'course_id')->toArray();
                } catch (Exception $e) {
                    Log::warning('Error checking participants: '.$e->getMessage());
                }
            }

            $pageTitle = 'TIK w pracy NAUCZYCIELA';
            $showCertificateLinksOnFreeList = false;

            return view('courses.free', compact('courses', 'sort', 'searchQuery', 'participantCourseIds', 'participantIdsByCourse', 'pageTitle', 'showCertificateLinksOnFreeList'));
        } catch (Exception $e) {
            Log::error('Error accessing free courses: '.$e->getMessage());

            return view('courses.free', [
                'courses' => collect([]),
                'databaseError' => true,
                'pageTitle' => 'TIK w pracy NAUCZYCIELA',
                'showCertificateLinksOnFreeList' => false,
            ]);
        }
    }

    /**
     * Wyświetl listę szkoleń dla serii Office 365 (course_series_id = 2).
     *
     * @return \Illuminate\View\View
     */
    public function office365Courses(Request $request)
    {
        try {
            $sort = $request->query('sort', 'desc');
            $sort = in_array($sort, ['asc', 'desc']) ? $sort : 'desc';
            $searchQuery = $request->query('q');

            // Pobierz wszystkie course_id z course_series_course dla serii o id = 2
            $seriesCourseIds = DB::connection('pneadm')
                ->table('course_series_course')
                ->where('course_series_id', 2)
                ->pluck('course_id')
                ->toArray();

            if (empty($seriesCourseIds)) {
                // Jeśli brak kursów w serii, zwróć pustą kolekcję
                $courses = Course::whereIn('id', [0])->paginate(20);
            } else {
                // Pobierz kursy z courses na podstawie course_id z course_series_course
                $coursesQuery = Course::with(['instructor', 'onlineDetail'])
                    ->whereIn('id', $seriesCourseIds)
                    ->where('is_active', true);

                if (! empty($searchQuery)) {
                    $coursesQuery->where(function ($q) use ($searchQuery) {
                        $q->where('title', 'like', '%'.$searchQuery.'%')
                            ->orWhere('description', 'like', '%'.$searchQuery.'%');
                    });
                }

                // Sortowanie według daty rozpoczęcia
                $courses = $coursesQuery
                    ->orderBy('start_date', $sort)
                    ->paginate(20)
                    ->appends([
                        'sort' => $sort,
                        'q' => $searchQuery,
                    ]);
            }

            // Sprawdź uczestnictwo dla zalogowanego użytkownika
            $userEmail = auth()->check() ? auth()->user()->email : null;
            $participantCourseIds = [];
            $participantIdsByCourse = []; // Mapowanie course_id => participant_id

            if ($userEmail) {
                try {
                    $normalizedEmail = strtolower(trim($userEmail));
                    $participants = \App\Models\Participant::query()
                        ->forNormalizedEmail($normalizedEmail)
                        ->select('id', 'course_id')
                        ->get();

                    $participantCourseIds = $participants->pluck('course_id')->toArray();
                    $participantIdsByCourse = $participants->pluck('id', 'course_id')->toArray();
                } catch (Exception $e) {
                    Log::warning('Error checking participants: '.$e->getMessage());
                }
            }

            $pageTitle = 'Szkolny ADMINISTRATOR Office 365';

            return view('courses.free', compact('courses', 'sort', 'searchQuery', 'participantCourseIds', 'participantIdsByCourse', 'pageTitle'));
        } catch (Exception $e) {
            Log::error('Error accessing office365 courses: '.$e->getMessage());

            return view('courses.free', [
                'courses' => collect([]),
                'databaseError' => true,
                'pageTitle' => 'Szkolny ADMINISTRATOR Office 365',
            ]);
        }
    }

    /**
     * Wyświetl listę szkoleń dla serii Akademia Rodzica (course_series_id = 3).
     *
     * @return \Illuminate\View\View
     */
    public function parentAcademyCourses(Request $request)
    {
        try {
            $sort = $request->query('sort', 'desc');
            $sort = in_array($sort, ['asc', 'desc']) ? $sort : 'desc';
            $searchQuery = $request->query('q');

            // Pobierz wszystkie course_id z course_series_course dla serii o id = 3
            $seriesCourseIds = DB::connection('pneadm')
                ->table('course_series_course')
                ->where('course_series_id', 3)
                ->pluck('course_id')
                ->toArray();

            if (empty($seriesCourseIds)) {
                // Jeśli brak kursów w serii, zwróć pustą kolekcję
                $courses = Course::whereIn('id', [0])->paginate(20);
            } else {
                // Pobierz kursy z courses na podstawie course_id z course_series_course
                $coursesQuery = Course::with(['instructor', 'onlineDetail'])
                    ->whereIn('id', $seriesCourseIds)
                    ->where('is_active', true);

                if (! empty($searchQuery)) {
                    $coursesQuery->where(function ($q) use ($searchQuery) {
                        $q->where('title', 'like', '%'.$searchQuery.'%')
                            ->orWhere('description', 'like', '%'.$searchQuery.'%');
                    });
                }

                // Sortowanie według daty rozpoczęcia
                $courses = $coursesQuery
                    ->orderBy('start_date', $sort)
                    ->paginate(20)
                    ->appends([
                        'sort' => $sort,
                        'q' => $searchQuery,
                    ]);
            }

            // Sprawdź uczestnictwo dla zalogowanego użytkownika
            $userEmail = auth()->check() ? auth()->user()->email : null;
            $participantCourseIds = [];
            $participantIdsByCourse = []; // Mapowanie course_id => participant_id

            if ($userEmail) {
                try {
                    $normalizedEmail = strtolower(trim($userEmail));
                    $participants = \App\Models\Participant::query()
                        ->forNormalizedEmail($normalizedEmail)
                        ->select('id', 'course_id')
                        ->get();

                    $participantCourseIds = $participants->pluck('course_id')->toArray();
                    $participantIdsByCourse = $participants->pluck('id', 'course_id')->toArray();
                } catch (Exception $e) {
                    Log::warning('Error checking participants: '.$e->getMessage());
                }
            }

            $pageTitle = 'Akademia Rodzica';

            return view('courses.free', compact('courses', 'sort', 'searchQuery', 'participantCourseIds', 'participantIdsByCourse', 'pageTitle'));
        } catch (Exception $e) {
            Log::error('Error accessing parent academy courses: '.$e->getMessage());

            return view('courses.free', [
                'courses' => collect([]),
                'databaseError' => true,
                'pageTitle' => 'Akademia Rodzica',
            ]);
        }
    }

    /**
     * Wyświetl listę szkoleń dla serii Akademia Dyrektora (course_series_id = 4).
     *
     * @return \Illuminate\View\View
     */
    public function directorAcademyCourses(Request $request)
    {
        try {
            $sort = $request->query('sort', 'desc');
            $sort = in_array($sort, ['asc', 'desc']) ? $sort : 'desc';
            $searchQuery = $request->query('q');

            // Pobierz wszystkie course_id z course_series_course dla serii o id = 4
            $seriesCourseIds = DB::connection('pneadm')
                ->table('course_series_course')
                ->where('course_series_id', 4)
                ->pluck('course_id')
                ->toArray();

            if (empty($seriesCourseIds)) {
                // Jeśli brak kursów w serii, zwróć pustą kolekcję
                $courses = Course::whereIn('id', [0])->paginate(20);
            } else {
                // Pobierz kursy z courses na podstawie course_id z course_series_course
                $coursesQuery = Course::with(['instructor', 'onlineDetail'])
                    ->whereIn('id', $seriesCourseIds)
                    ->where('is_active', true);

                if (! empty($searchQuery)) {
                    $coursesQuery->where(function ($q) use ($searchQuery) {
                        $q->where('title', 'like', '%'.$searchQuery.'%')
                            ->orWhere('description', 'like', '%'.$searchQuery.'%');
                    });
                }

                // Sortowanie według daty rozpoczęcia
                $courses = $coursesQuery
                    ->orderBy('start_date', $sort)
                    ->paginate(20)
                    ->appends([
                        'sort' => $sort,
                        'q' => $searchQuery,
                    ]);
            }

            // Sprawdź uczestnictwo dla zalogowanego użytkownika
            $userEmail = auth()->check() ? auth()->user()->email : null;
            $participantCourseIds = [];
            $participantIdsByCourse = []; // Mapowanie course_id => participant_id

            if ($userEmail) {
                try {
                    $normalizedEmail = strtolower(trim($userEmail));
                    $participants = \App\Models\Participant::query()
                        ->forNormalizedEmail($normalizedEmail)
                        ->select('id', 'course_id')
                        ->get();

                    $participantCourseIds = $participants->pluck('course_id')->toArray();
                    $participantIdsByCourse = $participants->pluck('id', 'course_id')->toArray();
                } catch (Exception $e) {
                    Log::warning('Error checking participants: '.$e->getMessage());
                }
            }

            $pageTitle = 'Akademia Dyrektora';

            return view('courses.free', compact('courses', 'sort', 'searchQuery', 'participantCourseIds', 'participantIdsByCourse', 'pageTitle'));
        } catch (Exception $e) {
            Log::error('Error accessing director academy courses: '.$e->getMessage());

            return view('courses.free', [
                'courses' => collect([]),
                'databaseError' => true,
                'pageTitle' => 'Akademia Dyrektora',
            ]);
        }
    }

    /**
     * Wyświetl listę szkoleń indywidualnych (te same co na stronie głównej).
     *
     * @return \Illuminate\View\View
     */
    public function individualCourses(Request $request)
    {
        // Nadchodzące: ten sam zestaw co homepage/sidebar (instructor + priceVariants).
        $upcomingCourses = \App\Support\UpcomingPneduCourses::query();

        // Archiwalne szkolenia (zakończone):
        // - nowe: kursy oznaczone "Pokaż na stronie głównej pnedu.pl"
        // - legacy (wsteczna zgodność): certgen_Publigo + id_old
        $archivedSearch = trim((string) $request->query('q', ''));

        $archivedQuery = Course::with('instructor')
            ->where('is_active', true)
            ->where('type', 'online')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->where('show_on_pnedu', true)
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->where('source_id_old', 'certgen_Publigo')
                            ->whereNotNull('id_old');
                    });
            })
            ->where(function ($query) {
                $query->where(function ($q) {
                    // Szkolenia z datą zakończenia w przeszłości
                    $q->whereNotNull('end_date')
                        ->where('end_date', '<', now());
                })->orWhere(function ($q) {
                    // Szkolenia bez daty zakończenia, ale z datą rozpoczęcia w przeszłości (starsze niż 30 dni)
                    $q->whereNull('end_date')
                        ->where('start_date', '<', now()->subDays(30));
                });
            });

        $hasArchivedCourses = \Illuminate\Support\Facades\Cache::remember(
            'courses.individual.has_archived.v1',
            now()->addMinutes(5),
            fn () => (clone $archivedQuery)->exists()
        );

        if ($archivedSearch !== '') {
            $archivedQuery->where('title', 'like', '%'.$archivedSearch.'%');
        }

        $archivedCourses = $archivedQuery
            ->orderBy('start_date', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->fragment('szkolenia-zakonczone');

        $showArchivedSection = $hasArchivedCourses || $archivedSearch !== '';

        if ($request->ajax() && $request->boolean('load_more')) {
            return response()->json([
                'html' => view('courses.partials.archived-courses-items', compact('archivedCourses'))->render(),
                'next_page_url' => $archivedCourses->nextPageUrl(),
                'has_more' => $archivedCourses->hasMorePages(),
            ]);
        }

        return view('courses.individual', compact(
            'upcomingCourses',
            'archivedCourses',
            'archivedSearch',
            'showArchivedSection',
        ));
    }

    /**
     * Wyświetl szczegóły szkolenia.
     */
    public function show($id)
    {
        $course = \App\Models\Course::with(['instructor', 'priceVariants', 'onlineDetail'])->findOrFail($id);

        $paymentOptions = \App\Models\PaymentDisplayOption::getForCoursePage();

        $activeCoursePriceVariants = $this->activePriceVariantsOrdered($course);

        return view('courses.show', compact('course', 'paymentOptions', 'activeCoursePriceVariants'));
    }

    /**
     * Zapis na bezpłatne szkolenie – dodanie e-maila do list Sendy (TIK, opcjonalnie NAUCZYCIELE).
     * Po przesłaniu przekierowanie na stronę główną z komunikatem w sesji.
     */
    public function register(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'email' => ['required', 'email:rfc,dns'],
                'rodo_consent' => ['required', 'accepted'],
                'newsletter_consent' => ['sometimes', 'boolean'],
            ], [
                'email.required' => 'Podaj adres e-mail.',
                'email.email' => 'Podaj prawidłowy adres e-mail.',
                'rodo_consent.accepted' => 'Musisz wyrazić zgodę na przetwarzanie danych osobowych.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Wystąpił błąd w formularzu.';

            return redirect()->route('home')
                ->with('course_registration_success', false)
                ->with('course_registration_message', $message);
        }

        $email = $validated['email'];
        $newsletterConsent = filter_var($request->input('newsletter_consent'), FILTER_VALIDATE_BOOLEAN);

        $sendyUrl = config('services.sendy.url');
        $sendyApiKey = config('services.sendy.api_key');

        if (empty($sendyUrl) || empty($sendyApiKey)) {
            Log::warning('Sendy not configured: missing SENDY_URL or SENDY_API_KEY');

            return redirect()->route('home')
                ->with('course_registration_success', false)
                ->with('course_registration_message', 'Zapis na szkolenie jest tymczasowo niedostępny. Spróbuj później.');
        }

        $sendy = new SendyService($sendyUrl, $sendyApiKey);
        $result = $sendy->subscribeCourseRegistration($email, $newsletterConsent);

        if (! $result['tik']) {
            return redirect()->route('home')
                ->with('course_registration_success', false)
                ->with('course_registration_message', 'Nie udało się zapisać na listę. Sprawdź adres e-mail lub spróbuj później.');
        }

        return redirect()->route('home')
            ->with('course_registration_success', true)
            ->with('course_registration_message', 'Dziękujemy! Zostałeś zapisany na szkolenie. Na podany adres e-mail wyślemy potwierdzenie i link do spotkania.');
    }

    /**
     * Wyświetl stronę płatności online.
     */
    public function payOnline($id)
    {
        $course = \App\Models\Course::findOrFail($id);
        $displayOptions = PaymentDisplayOption::getForCoursePage();
        $developerSymbolicPayment = DeveloperOnlinePaymentTest::shouldApplySymbolicAmount($displayOptions, auth()->user());

        return view('courses.pay-online', compact('course', 'developerSymbolicPayment'));
    }

    /**
     * Obsługa wysłania formularza płatności online.
     */
    public function storePayOnline(Request $request, $id)
    {
        $course = \App\Models\Course::findOrFail($id);

        $rules = [
            'buyer_type' => 'nullable|in:person,company,organisation',
            'payment_gateway' => 'required|in:paynow,payu',
            'email' => 'required|email',
            'email_confirmation' => 'required|email|same:email',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'order_comment' => 'nullable|string|max:2000',
        ];

        $messages = [
            'buyer_type.required' => 'Wybierz typ zamawiającego.',
            'buyer_type.in' => 'Wybierz prawidłowy typ zamawiającego.',
            'payment_gateway.required' => 'Wybierz bramkę płatności.',
            'payment_gateway.in' => 'Wybierz prawidłową bramkę płatności.',
            'email.required' => 'Adres e-mail jest wymagany.',
            'email.email' => 'Podaj prawidłowy adres e-mail.',
            'email_confirmation.required' => 'Powtórzenie adresu e-mail jest wymagane.',
            'email_confirmation.same' => 'Adresy e-mail muszą być identyczne.',
            'first_name.required' => 'Imię jest wymagane.',
            'last_name.required' => 'Nazwisko jest wymagane.',
            'phone.required' => 'Numer telefonu jest wymagany.',
        ];

        $buyerType = $request->input('buyer_type', 'person');

        // Logika walidacji faktury:
        // - Osoba fizyczna: faktura opcjonalna (wszystkie pola nullable)
        // - Firma: faktura obowiązkowa (wszystkie pola required)
        // - Instytucja: NABYWCA obowiązkowy, ODBIORCA opcjonalny (ale jeśli podane dane odbiorcy, to recipient_nip required)
        if ($buyerType === 'person') {
            $rules = array_merge($rules, [
                'person_full_name' => 'nullable|string|max:255',
                'person_street' => 'nullable|string|max:255',
                'person_building_no' => 'nullable|string|max:20',
                'person_flat_no' => 'nullable|string|max:20',
                'person_postcode' => 'nullable|string|max:20',
                'person_city' => 'nullable|string|max:255',
                'person_country' => 'nullable|string|max:255',
            ]);
        } elseif ($buyerType === 'company') {
            // Firma - faktura obowiązkowa
            $rules = array_merge($rules, [
                'company_nip' => 'required|string|max:20',
                'company_country' => 'required|string|max:255',
                'company_name' => 'required|string|max:255',
                'company_street' => 'required|string|max:255',
                'company_building_no' => 'required|string|max:20',
                'company_flat_no' => 'nullable|string|max:20',
                'company_postcode' => 'required|string|max:20',
                'company_city' => 'required|string|max:255',
            ]);
        } elseif ($buyerType === 'organisation') {
            // Instytucja - NABYWCA obowiązkowy, ODBIORCA opcjonalny
            $rules = array_merge($rules, [
                'buyer_nip' => 'required|string|max:20',
                'buyer_country' => 'required|string|max:255',
                'buyer_name' => 'required|string|max:255',
                'buyer_street' => 'required|string|max:255',
                'buyer_building_no' => 'required|string|max:20',
                'buyer_flat_no' => 'nullable|string|max:20',
                'buyer_postcode' => 'required|string|max:20',
                'buyer_city' => 'required|string|max:255',
                // ODBIORCA - opcjonalny, ale jeśli podane jakiekolwiek dane, to recipient_nip required
                'recipient_nip' => 'nullable|string|max:20',
                'recipient_country' => 'nullable|string|max:255',
                'recipient_name' => 'nullable|string|max:255',
                'recipient_street' => 'nullable|string|max:255',
                'recipient_building_no' => 'nullable|string|max:20',
                'recipient_flat_no' => 'nullable|string|max:20',
                'recipient_postcode' => 'nullable|string|max:20',
                'recipient_city' => 'nullable|string|max:255',
            ]);
        }

        $request->validate($rules, $messages);

        // Dodatkowa walidacja dla instytucji: jeśli podane dane odbiorcy, to recipient_nip jest wymagany
        if ($buyerType === 'organisation') {
            $hasRecipientData = $request->filled('recipient_name') ||
                                $request->filled('recipient_street') ||
                                $request->filled('recipient_city') ||
                                $request->filled('recipient_postcode') ||
                                $request->filled('recipient_country');

            if ($hasRecipientData && ! $request->filled('recipient_nip')) {
                return redirect()->back()
                    ->withErrors(['recipient_nip' => 'NIP odbiorcy jest wymagany, jeśli podano dane odbiorcy.'])
                    ->withInput();
            }
        }

        $paymentGateway = $request->input('payment_gateway', 'paynow');

        if ($paymentGateway === 'payu') {
            return $this->processPayUPayment($request, $course);
        }

        if ($paymentGateway === 'paynow') {
            return $this->processPayNowPayment($request, $course);
        }

        return redirect()->route('payment.online', $course->id)
            ->with('error', 'Nieznana bramka płatności.');
    }

    /**
     * Przetwórz płatność PayU – utwórz zamówienie i przekieruj do bramki.
     */
    protected function processPayUPayment(Request $request, $course)
    {
        $priceInfo = $course->getCurrentPrice();
        $totalAmount = $this->resolveOnlineCheckoutAmount((float) ($priceInfo['price'] ?? 0));

        if ($totalAmount <= 0) {
            return redirect()->route('payment.online', $course->id)
                ->with('error', 'To szkolenie nie ma ustawionej ceny. Skontaktuj się z organizatorem lub wybierz formularz zamówienia z odroczonym terminem płatności.')
                ->withInput();
        }

        $addressData = $this->collectAddressData($request);
        $formData = $request->except(['_token', 'email_confirmation']);

        $order = \App\Models\OnlinePaymentOrder::create([
            'ident' => \App\Models\OnlinePaymentOrder::generateIdent(),
            'course_id' => $course->id,
            'payment_gateway' => 'payu',
            'status' => \App\Models\OnlinePaymentOrder::STATUS_PENDING,
            'total_amount' => $totalAmount,
            'currency' => 'PLN',
            'buyer_type' => $request->input('buyer_type'),
            'email' => $request->input('email'),
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'phone' => $request->input('phone'),
            'order_comment' => $request->input('order_comment'),
            'address_data' => $addressData,
            'form_data' => $formData,
            'ip_address' => $request->ip(),
        ]);

        $payuService = $this->makePayUService();
        $notifyUrl = route('payment.payu.notify');
        $continueUrl = route('payment.payu.return');

        $result = $payuService->createOrder($order, $notifyUrl, $continueUrl);

        if (! $result['success']) {
            $errorMsg = $result['error'] ?? 'Nie udało się połączyć z PayU. Spróbuj ponownie.';
            if (str_contains($errorMsg, 'tokenu')) {
                $errorMsg .= ' Sprawdź konfigurację w .env (PAYU_CLIENT_ID, PAYU_CLIENT_SECRET, PAYU_SANDBOX) oraz logi: storage/logs/laravel.log';
            }

            return redirect()->route('payment.online', $course->id)
                ->with('error', $errorMsg)
                ->withInput();
        }

        // Zapisz ident zamówienia w sesji jako fallback dla return URL
        // PayU może nie przekazywać parametrów w return URL
        session(['payu_order_ident' => $order->ident]);
        session(['payu_order_email' => $order->email]);

        return redirect()->away($result['redirect_uri']);
    }

    /**
     * Przetwórz płatność PayNow – utwórz zamówienie i przekieruj do bramki.
     */
    protected function processPayNowPayment(Request $request, $course)
    {
        $priceInfo = $course->getCurrentPrice();
        $totalAmount = $this->resolveOnlineCheckoutAmount((float) ($priceInfo['price'] ?? 0));

        if ($totalAmount <= 0) {
            return redirect()->route('payment.online', $course->id)
                ->with('error', 'To szkolenie nie ma ustawionej ceny. Skontaktuj się z organizatorem lub wybierz formularz zamówienia z odroczonym terminem płatności.')
                ->withInput();
        }

        $addressData = $this->collectAddressData($request);
        $formData = $request->except(['_token', 'email_confirmation']);

        $order = \App\Models\OnlinePaymentOrder::create([
            'ident' => \App\Models\OnlinePaymentOrder::generateIdent(),
            'course_id' => $course->id,
            'payment_gateway' => 'paynow',
            'status' => \App\Models\OnlinePaymentOrder::STATUS_PENDING,
            'total_amount' => $totalAmount,
            'currency' => 'PLN',
            'buyer_type' => $request->input('buyer_type'),
            'email' => $request->input('email'),
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'phone' => $request->input('phone'),
            'order_comment' => $request->input('order_comment'),
            'address_data' => $addressData,
            'form_data' => $formData,
            'ip_address' => $request->ip(),
        ]);

        $paynowService = $this->makePayNowService();
        $notifyUrl = route('payment.paynow.notify');
        $continueUrl = route('payment.paynow.return');

        $result = $paynowService->createOrder($order, $notifyUrl, $continueUrl);

        if (! $result['success']) {
            $errorMsg = $result['error'] ?? 'Nie udało się połączyć z PayNow. Spróbuj ponownie.';
            if (str_contains($errorMsg, 'konfiguracji')) {
                $errorMsg .= ' Sprawdź konfigurację w .env (PAYNOW_API_KEY, PAYNOW_SIGNATURE_KEY, PAYNOW_SANDBOX) oraz logi: storage/logs/laravel.log';
            }

            return redirect()->route('payment.online', $course->id)
                ->with('error', $errorMsg)
                ->withInput();
        }

        return redirect()->away($result['redirect_url']);
    }

    /**
     * Zbierz dane adresowe z requesta w zależności od buyer_type.
     */
    protected function collectAddressData(Request $request): array
    {
        $type = $request->input('buyer_type', 'person');

        if ($type === 'person') {
            return [
                'full_name' => $request->input('person_full_name'),
                'street' => $request->input('person_street'),
                'building_no' => $request->input('person_building_no'),
                'flat_no' => $request->input('person_flat_no'),
                'postcode' => $request->input('person_postcode'),
                'city' => $request->input('person_city'),
                'country' => $request->input('person_country'),
            ];
        }

        if ($type === 'company') {
            return [
                'nip' => $request->input('company_nip'),
                'country' => $request->input('company_country'),
                'name' => $request->input('company_name'),
                'street' => $request->input('company_street'),
                'building_no' => $request->input('company_building_no'),
                'flat_no' => $request->input('company_flat_no'),
                'postcode' => $request->input('company_postcode'),
                'city' => $request->input('company_city'),
            ];
        }

        if ($type === 'organisation') {
            return [
                'buyer' => [
                    'nip' => $request->input('buyer_nip'),
                    'country' => $request->input('buyer_country'),
                    'name' => $request->input('buyer_name'),
                    'street' => $request->input('buyer_street'),
                    'building_no' => $request->input('buyer_building_no'),
                    'flat_no' => $request->input('buyer_flat_no'),
                    'postcode' => $request->input('buyer_postcode'),
                    'city' => $request->input('buyer_city'),
                ],
                'recipient' => [
                    'nip' => $request->input('recipient_nip'),
                    'country' => $request->input('recipient_country'),
                    'name' => $request->input('recipient_name'),
                    'street' => $request->input('recipient_street'),
                    'building_no' => $request->input('recipient_building_no'),
                    'flat_no' => $request->input('recipient_flat_no'),
                    'postcode' => $request->input('recipient_postcode'),
                    'city' => $request->input('recipient_city'),
                ],
            ];
        }

        return [];
    }

    /**
     * Wyświetl formularz zamówienia z odroczonym terminem płatności.
     */
    public function deferredOrder($id, $ident = null)
    {
        $course = \App\Models\Course::with('priceVariants')->findOrFail($id);
        $existingOrder = null;

        // Sprawdź czy to tryb testowy (URL kończy się na /test)
        $isTestMode = Str::endsWith(request()->path(), '/deferred-order/test');

        // Sprawdź czy to edycja istniejącego zamówienia
        $orderData = [];
        $isEditMode = false;

        if ($ident) {
            $existingOrder = FormOrder::withTrashed()
                ->where('ident', $ident)
                ->where('product_id', $id)
                ->first();
            if (! $existingOrder) {
                return redirect()
                    ->route('payment.order-form', $id)
                    ->with('info', $this->messageWhenOrderEditLinkNotFound());
            }

            if ($existingOrder->isEditLocked()) {
                return $this->renderOrderEditLockedView($course, $existingOrder);
            }

            $isEditMode = true;
            $participantPrefill = $this->participantPrefillFromFormOrder($existingOrder);
            // Wczytaj dane z zamówienia
            $orderData = [
                'buyer_name' => $existingOrder->buyer_name,
                'buyer_address' => $existingOrder->buyer_address,
                'buyer_postcode' => $existingOrder->buyer_postal_code,
                'buyer_city' => $existingOrder->buyer_city,
                'buyer_nip' => $existingOrder->buyer_nip,
                'recipient_name' => $existingOrder->recipient_name,
                'recipient_address' => $existingOrder->recipient_address,
                'recipient_postcode' => $existingOrder->recipient_postal_code,
                'recipient_city' => $existingOrder->recipient_city,
                'recipient_nip' => $existingOrder->recipient_nip,
                'contact_name' => $existingOrder->orderer_name,
                'contact_phone' => $existingOrder->orderer_phone,
                'contact_email' => $existingOrder->orderer_email,
                'participant_first_name' => $participantPrefill['participant_first_name'],
                'participant_last_name' => $participantPrefill['participant_last_name'],
                'participant_email' => $participantPrefill['participant_email'],
                'invoice_notes' => $existingOrder->invoice_notes,
                'payment_terms' => $existingOrder->invoice_payment_delay ?? $existingOrder->ptw,
                'order_id' => $existingOrder->id,
                'order_ident' => $existingOrder->ident,
                'fb_source' => $existingOrder->fb_source,
                'conversion_placement' => $existingOrder->conversion_placement,
                'price_variant_id' => $existingOrder->course_price_variant_id,
            ];
        }

        $redirect = $this->enforcePriceVariantFromQueryOrRedirect($course, $ident);
        if ($redirect) {
            return $redirect;
        }

        $prefillPriceVariantId = $this->prefillPriceVariantIdForPublicOrderForm($course, $ident, $orderData);

        // Bez automatycznego uzupełniania danymi testowymi przy wejściu — tylko przycisk ręcznego wypełnienia.
        $testData = $orderData;

        [$testData, $checkoutResumeBanner] = $this->prepareOrderFormCheckoutResume(
            (int) $id,
            $testData,
            $isEditMode,
            'payment.deferred.edit',
            'payment.deferred',
            $this->orderFormResumeRouteParams($prefillPriceVariantId)
        );

        // Pobierz dane zalogowanego użytkownika (jeśli jest zalogowany)
        $user = auth()->user();

        $fbSourceDefault = $this->resolveFbSourceDefaultForForm($existingOrder);
        $conversionPlacementDefault = $this->resolveConversionPlacementDefaultForForm((int) $id, $existingOrder);

        return view('courses.deferred-order', compact('course', 'testData', 'isTestMode', 'isEditMode', 'user', 'prefillPriceVariantId', 'fbSourceDefault', 'conversionPlacementDefault', 'checkoutResumeBanner'));
    }

    /**
     * Wyświetl nowy formularz zamówienia (kopia, do dalszych zmian).
     */
    /**
     * Bramka GET /order-form — rozstrzyga legacy vs V2 (edycja zamówienia zawsze legacy).
     */
    public function orderForm($id, $ident = null)
    {
        $course = \App\Models\Course::with('priceVariants')->findOrFail($id);
        $existingOrder = null;

        $displayOptions = \App\Models\PaymentDisplayOption::getForCoursePage();

        if (! $ident) {
            $gateway = app(\App\Support\OrderFormGateway::class);
            $variant = $gateway->resolveVariant(request(), $displayOptions);
            $gateway->markResolvedVariant(request(), $variant);

            if ($variant === \App\Support\OrderFormVariant::V2) {
                $course = \App\Models\Course::with(['priceVariants', 'instructor', 'onlineDetail'])->findOrFail($id);

                return $this->renderNewOrderForm(
                    course: $course,
                    displayOptions: $displayOptions,
                    viewName: 'courses.order-form-v2',
                    routeName: \App\Support\OrderFormVariant::publicRouteName()
                );
            }

            return $this->renderNewOrderForm(
                course: $course,
                displayOptions: $displayOptions,
                viewName: 'courses.order-form',
                routeName: \App\Support\OrderFormVariant::publicRouteName()
            );
        }

        // Tryb testowy: ?test=1 włącza, ?test=0 wyłącza. Bez parametru – ustawienie z panelu (Zakupy pnedu.pl).
        $isTestMode = request()->has('test')
            ? (bool) request()->boolean('test')
            : \App\Models\PaymentDisplayOption::isOrderFormTestModeEnabled($displayOptions, auth()->user());

        // Sprawdź czy to edycja istniejącego zamówienia (opcjonalnie, przez ident)
        $orderData = [];
        $isEditMode = false;

        if ($ident) {
            $existingOrder = FormOrder::withTrashed()
                ->where('ident', $ident)
                ->where('product_id', $id)
                ->first();
            if (! $existingOrder) {
                return redirect()
                    ->route('payment.order-form', $id)
                    ->with('info', $this->messageWhenOrderEditLinkNotFound());
            }

            if ($existingOrder->isEditLocked()) {
                return $this->renderOrderEditLockedView($course, $existingOrder);
            }

            $isEditMode = true;
            $orderData = $this->orderFormPrefillFromFormOrder($existingOrder);
        }

        $redirect = $this->enforcePriceVariantFromQueryOrRedirect($course, $ident);
        if ($redirect) {
            return $redirect;
        }

        $prefillPriceVariantId = $this->prefillPriceVariantIdForPublicOrderForm($course, $ident, $orderData);

        // Bez automatycznego uzupełniania danymi testowymi przy wejściu na formularz:
        // tryb testowy udostępnia tylko przycisk ręcznego wypełnienia.
        [$testData, $checkoutResumeBanner] = $this->prepareOrderFormCheckoutResume(
            (int) $id,
            $orderData,
            $isEditMode,
            'payment.order-form.edit',
            'payment.order-form',
            $this->orderFormResumeRouteParams($prefillPriceVariantId)
        );

        $user = auth()->user();
        $fbSourceDefault = $this->resolveFbSourceDefaultForForm($existingOrder);
        $conversionPlacementDefault = $this->resolveConversionPlacementDefaultForForm((int) $id, $existingOrder);
        $developerSymbolicPayment = DeveloperOnlinePaymentTest::shouldApplySymbolicAmount($displayOptions, $user);

        return view('courses.order-form', compact('course', 'testData', 'isTestMode', 'isEditMode', 'user', 'prefillPriceVariantId', 'fbSourceDefault', 'conversionPlacementDefault', 'checkoutResumeBanner', 'developerSymbolicPayment'));
    }

    /**
     * Bezpośredni URL V2 (QA / stare linki /order-form-v2). Nowe linki używają bramy /order-form.
     */
    public function orderFormV2($id)
    {
        $displayOptions = \App\Models\PaymentDisplayOption::getForCoursePage();
        abort_unless($displayOptions['show_order_form_v2'] ?? false, 404);

        app(\App\Support\OrderFormGateway::class)->markResolvedVariant(request(), \App\Support\OrderFormVariant::V2);

        $course = \App\Models\Course::with(['priceVariants', 'instructor', 'onlineDetail'])->findOrFail($id);

        return $this->renderNewOrderForm(
            course: $course,
            displayOptions: $displayOptions,
            viewName: 'courses.order-form-v2',
            routeName: \App\Support\OrderFormVariant::publicRouteName()
        );
    }

    /**
     * Wspólne przygotowanie danych nowego zamówienia dla formularza stabilnego i V2.
     */
    protected function renderNewOrderForm(Course $course, array $displayOptions, string $viewName, string $routeName)
    {
        $isTestMode = request()->has('test')
            ? (bool) request()->boolean('test')
            : \App\Models\PaymentDisplayOption::isOrderFormTestModeEnabled($displayOptions, auth()->user());

        $redirect = $this->enforcePriceVariantFromQueryOrRedirect($course, null);
        if ($redirect) {
            return $redirect;
        }

        $isEditMode = false;
        $prefillPriceVariantId = $this->prefillPriceVariantIdForPublicOrderForm($course, null, []);

        $testData = [];

        [$testData, $checkoutResumeBanner] = $this->prepareOrderFormCheckoutResume(
            (int) $course->id,
            $testData,
            false,
            'payment.order-form.edit',
            $routeName,
            $this->orderFormResumeRouteParams($prefillPriceVariantId)
        );

        $testData = $this->mergePrefillFromUnpaidOnlineOrder($course, $testData);

        $user = auth()->user();
        $fbSourceDefault = $this->resolveFbSourceDefaultForForm(null);
        $conversionPlacementDefault = $this->resolveConversionPlacementDefaultForForm((int) $course->id, null);
        $developerSymbolicPayment = DeveloperOnlinePaymentTest::shouldApplySymbolicAmount($displayOptions, $user);

        return view($viewName, compact('course', 'testData', 'isTestMode', 'isEditMode', 'user', 'prefillPriceVariantId', 'fbSourceDefault', 'conversionPlacementDefault', 'checkoutResumeBanner', 'developerSymbolicPayment'));
    }

    /**
     * Sprawdzenie, czy w bazie participants jest już uczestnik z podanym e-mailem (dla autouzupełnienia w formularzu zamówienia).
     * GET ?email=...
     */
    public function participantLookupByEmail(Request $request)
    {
        $email = $request->query('email');
        $email = $email ? trim($email) : '';
        if ($email === '' || strpos($email, '@') === false) {
            return response()->json(['found' => false], 200, ['Content-Type' => 'application/json']);
        }
        $normalized = strtolower($email);
        $participant = Participant::whereRaw('LOWER(TRIM(email)) = ?', [$normalized])
            ->orderByDesc('id')
            ->first();
        if (! $participant) {
            return response()->json(['found' => false], 200, ['Content-Type' => 'application/json']);
        }

        return response()->json([
            'found' => true,
            'first_name' => (string) ($participant->first_name ?? ''),
            'last_name' => (string) ($participant->last_name ?? ''),
            'birth_date' => $participant->birth_date ? $participant->birth_date->format('d.m.Y') : null,
            'birth_place' => $participant->birth_place ? (string) $participant->birth_place : null,
        ], 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Czy e-mail może być użyty jako uczestnik tego szkolenia (unikalność na kursie).
     */
    public function participantEmailAvailability(Request $request, $id)
    {
        $email = trim((string) $request->query('email', ''));
        $exceptIdent = trim((string) $request->query('except_ident', ''));
        $exceptOrderId = null;
        if ($exceptIdent !== '') {
            $exceptOrderId = FormOrder::query()
                ->where('ident', $exceptIdent)
                ->where('product_id', (int) $id)
                ->value('id');
            $exceptOrderId = $exceptOrderId ? (int) $exceptOrderId : null;
        }

        $result = app(OrderFormParticipantService::class)
            ->emailAvailability((int) $id, $email, $exceptOrderId);

        return response()->json($result, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Dane uczestnika do ponownego wypełnienia formularza (edycja zamówienia) – z form_order_participants, z fallbackiem.
     *
     * @return array{participant_first_name: string, participant_last_name: string, participant_email: string, participants: list<array{first_name: string, last_name: string, email: string}>}
     */
    protected function participantPrefillFromFormOrder(FormOrder $order): array
    {
        $order->loadMissing(['participants' => fn ($q) => $q->orderBy('id')]);
        $rows = [];
        foreach ($order->participants as $p) {
            $rows[] = [
                'first_name' => (string) ($p->participant_firstname ?? ''),
                'last_name' => (string) ($p->participant_lastname ?? ''),
                'email' => (string) ($p->participant_email ?? ''),
            ];
        }

        if ($rows === []) {
            $p = $order->primaryParticipant;
            if ($p) {
                $rows[] = [
                    'first_name' => (string) ($p->participant_firstname ?? ''),
                    'last_name' => (string) ($p->participant_lastname ?? ''),
                    'email' => (string) ($p->participant_email ?? ''),
                ];
            }
        }

        $primary = $rows[0] ?? [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
        ];

        if (($primary['first_name'] === '' && $primary['last_name'] === '') && trim($order->display_participant_name) !== '') {
            $segments = preg_split('/\s+/', trim($order->display_participant_name), 2);
            $primary['first_name'] = $segments[0] ?? '';
            $primary['last_name'] = $segments[1] ?? '';
            if ($rows === []) {
                $rows[] = $primary;
            } else {
                $rows[0] = $primary;
            }
        }

        if (($primary['email'] === '') && filled($order->display_participant_email)) {
            $primary['email'] = (string) $order->display_participant_email;
            if ($rows === []) {
                $rows[] = $primary;
            } else {
                $rows[0] = $primary;
            }
        }

        return [
            'participant_first_name' => $primary['first_name'],
            'participant_last_name' => $primary['last_name'],
            'participant_email' => $primary['email'],
            'participants' => $rows !== [] ? $rows : [$primary],
        ];
    }

    /**
     * Komunikat przy wejściu na link „edycji”, gdy zamówienia nie ma już w bazie (np. trwałe usunięcie w panelu adm).
     */
    protected function messageWhenOrderEditLinkNotFound(): string
    {
        return 'Zamówienia powiązanego z tym linkiem nie ma już w systemie — mogło zostać trwale usunięte przez administratora. Możesz wypełnić poniższy formularz i przesłać zamówienie ponownie; zostanie ono zarejestrowane jako nowe.';
    }

    /**
     * Prefill formularza z nieopłaconego zamówienia online (?prefill_from=) — np. przejście na FV odroczoną.
     *
     * @param  array<string, mixed>  $formData
     * @return array<string, mixed>
     */
    protected function mergePrefillFromUnpaidOnlineOrder(Course $course, array $formData): array
    {
        $prefillFromIdent = trim((string) request()->query('prefill_from', ''));
        if ($prefillFromIdent === '') {
            return $formData;
        }

        $prefillOrder = FormOrder::query()
            ->where('ident', $prefillFromIdent)
            ->where('product_id', $course->id)
            ->first();

        $retryService = app(\App\Services\FormOrderOnlinePaymentRetryService::class);
        if (! $prefillOrder || ! $retryService->canRetryPayment($prefillOrder)) {
            return $formData;
        }

        $prefill = $this->orderFormPrefillFromFormOrder($prefillOrder);
        unset($prefill['order_ident'], $prefill['order_id']);

        $requestedPaymentType = request()->query('payment_type');
        $prefill['payment_type'] = $requestedPaymentType === 'online' ? 'online' : 'deferred';

        return array_merge($formData, $prefill);
    }

    /**
     * Typ zamawiającego z zapisanego zamówienia: brak NIP nabywcy → osoba fizyczna (zapis z order-form).
     */
    protected function inferBuyerTypeFromFormOrder(FormOrder $order): string
    {
        return \App\Support\OrderFormCustomerProfile::buyerTypeForProfile(
            $this->inferCustomerProfileFromFormOrder($order)
        );
    }

    /**
     * Profil V2: person / school / organisation — wg NIP nabywcy i odbiorcy.
     */
    protected function inferCustomerProfileFromFormOrder(FormOrder $order): string
    {
        return \App\Support\OrderFormCustomerProfile::fromBuyerAndRecipient(
            $order->buyer_nip,
            $order->recipient_nip,
            $order->recipient_name
        );
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null}
     */
    protected function prepareOrderFormCheckoutResume(
        int $courseId,
        array $formData,
        bool $isEditMode,
        string $editRouteName,
        string $formRouteName,
        array $formRouteParams = []
    ): array {
        $resumeService = app(FormOrderCheckoutResumeService::class);

        if (request()->boolean('new_order')) {
            $resumeService->clearResumeForCourse($courseId);
        }

        $formData = $resumeService->mergeResumeIntoFormData($courseId, $formData, $isEditMode);
        $banner = $resumeService->resumeBannerContext(
            $courseId,
            $isEditMode,
            $editRouteName,
            $formRouteName,
            $formRouteParams
        );

        return [$formData, $banner];
    }

    /**
     * @return array<string, mixed>
     */
    protected function orderFormResumeRouteParams(?int $prefillPriceVariantId): array
    {
        $params = [];
        $variantId = request()->query('price_variant_id', $prefillPriceVariantId);
        if ($variantId !== null && $variantId !== '') {
            $params['price_variant_id'] = $variantId;
        }
        if (request()->filled('fb')) {
            $params['fb'] = request()->query('fb');
        }
        if (request()->filled(\App\Support\OrderFormGateway::QUERY_PARAM)) {
            $params[\App\Support\OrderFormGateway::QUERY_PARAM] = request()->query(\App\Support\OrderFormGateway::QUERY_PARAM);
        }

        return $params;
    }

    /**
     * @deprecated Użyj {@see FormOrderCheckoutResumeService::resolveForSubmit()}.
     */
    protected function resolveFormOrderForUpdate(?string $orderIdent, int $courseId): ?FormOrder
    {
        if ($orderIdent === null || $orderIdent === '') {
            return null;
        }

        $order = FormOrder::withTrashed()
            ->where('ident', $orderIdent)
            ->where('product_id', $courseId)
            ->first();

        if ($order && $order->trashed()) {
            if ($order->isEditLocked()) {
                return $order;
            }
            $order->restore();
            Log::info('FormOrder restored after customer resubmitted order form', [
                'ident' => $order->ident,
                'form_order_id' => $order->id,
            ]);
        }

        return $order;
    }

    /**
     * @deprecated Użyj {@see FormOrderCheckoutResumeService}.
     */
    protected function mergePendingOnlineCheckoutIdentIntoTestData(int $courseId, array $testData): array
    {
        return app(FormOrderCheckoutResumeService::class)
            ->mergeResumeIntoFormData($courseId, $testData, false);
    }

    /**
     * Widok tylko do odczytu dla zamówienia zablokowanego (faktura lub zamknięte).
     */
    protected function renderOrderEditLockedView(Course $course, FormOrder $order): \Illuminate\Contracts\View\View
    {
        $order->load(['participants' => fn ($q) => $q->orderBy('id')]);

        return view('orders.order-edit-locked', compact('course', 'order'));
    }

    /**
     * Dzieli "Imię Nazwisko" na dwa pola (pierwszy token + reszta).
     *
     * @return array{first: string, last: string}
     */
    protected function splitFullNameIntoFirstAndLast(string $full): array
    {
        $full = trim($full);
        if ($full === '') {
            return ['first' => '', 'last' => ''];
        }
        if (! preg_match('/^(\S+)\s+(.+)$/u', $full, $m)) {
            return ['first' => $full, 'last' => ''];
        }

        return ['first' => $m[1], 'last' => trim($m[2])];
    }

    /**
     * Prefill order-form przy edycji zamówienia (np. z podsumowania PDF).
     *
     * @return array<string, mixed>
     */
    protected function orderFormPrefillFromFormOrder(FormOrder $existingOrder): array
    {
        $participantPrefill = $this->participantPrefillFromFormOrder($existingOrder);
        $customerProfile = $this->inferCustomerProfileFromFormOrder($existingOrder);
        $buyerType = \App\Support\OrderFormCustomerProfile::buyerTypeForProfile($customerProfile);
        $recipientIdentity = app(OrderFormRecipientIdentityService::class)->prefillFromFormOrder($existingOrder);

        $orderData = [
            'customer_profile' => $customerProfile,
            'buyer_type' => $buyerType,
            'payment_type' => ($existingOrder->payment_mode === FormOrder::PAYMENT_MODE_ONLINE_GATEWAY) ? 'online' : 'deferred',
            'buyer_name' => $existingOrder->buyer_name,
            'buyer_address' => $existingOrder->buyer_address,
            'buyer_postcode' => $existingOrder->buyer_postal_code,
            'buyer_city' => $existingOrder->buyer_city,
            'buyer_nip' => $existingOrder->buyer_nip,
            'recipient_name' => $existingOrder->recipient_name,
            'recipient_address' => $existingOrder->recipient_address,
            'recipient_postcode' => $existingOrder->recipient_postal_code,
            'recipient_city' => $existingOrder->recipient_city,
            'recipient_nip' => $recipientIdentity['recipient_nip'] ?? $existingOrder->recipient_nip,
            'recipient_internal_id' => $recipientIdentity['recipient_internal_id'],
            'contact_phone' => $existingOrder->orderer_phone,
            'contact_email' => $existingOrder->orderer_email,
            'participant_first_name' => $participantPrefill['participant_first_name'],
            'participant_last_name' => $participantPrefill['participant_last_name'],
            'participant_email' => $participantPrefill['participant_email'],
            'participants' => $participantPrefill['participants'],
            'invoice_notes' => $existingOrder->invoice_notes,
            'payment_terms' => $existingOrder->invoice_payment_delay ?? $existingOrder->ptw,
            'order_id' => $existingOrder->id,
            'order_ident' => $existingOrder->ident,
            'fb_source' => $existingOrder->fb_source,
            'conversion_placement' => $existingOrder->conversion_placement,
            'price_variant_id' => $existingOrder->course_price_variant_id,
        ];

        $ordererName = trim((string) $existingOrder->orderer_name);
        $buyerName = trim((string) ($existingOrder->buyer_name ?? ''));

        if ($buyerType === 'person') {
            $ordererParts = $this->splitFullNameIntoFirstAndLast($ordererName);
            $orderData['contact_first_name'] = $ordererParts['first'];
            $orderData['contact_last_name'] = $ordererParts['last'];
            $orderData['contact_name'] = $ordererName;

            $buyerParts = $this->splitFullNameIntoFirstAndLast($buyerName);
            $orderData['buyer_person_first_name'] = $buyerParts['first'];
            $orderData['buyer_person_last_name'] = $buyerParts['last'];
        } else {
            $orderData['contact_name'] = $ordererName;
        }

        return $orderData;
    }

    /**
     * Źródło marketingowe (fb / kampania) – z ukrytego pola lub ?fb= w URL.
     * Przy edycji zamówienia zachowaj istniejące fb_source, jeśli pole jest puste.
     */
    protected function resolveFbSourceForFormOrder(array $validated, ?FormOrder $existingOrder = null): ?string
    {
        $raw = $validated['fb_source'] ?? null;
        if (is_string($raw)) {
            $raw = trim($raw);
        }

        if ($raw === null || $raw === '') {
            $raw = app(\App\Services\MarketingAttributionService::class)->resolveCampaignCode(request());
        }

        if ($raw !== null && $raw !== '') {
            return Str::limit($raw, 255, '');
        }
        if ($existingOrder && $existingOrder->fb_source) {
            return $existingOrder->fb_source;
        }

        return null;
    }

    /**
     * Domyślne źródło marketingowe do prefill formularza:
     * query (?fb / ?fb_source) -> sesja -> istniejące zamówienie (edycja).
     */
    protected function resolveFbSourceDefaultForForm(?FormOrder $existingOrder = null): ?string
    {
        $raw = app(\App\Services\MarketingAttributionService::class)->resolveCampaignCode(request());

        if ($raw !== null && $raw !== '') {
            return Str::limit($raw, 255, '');
        }

        if ($existingOrder && $existingOrder->fb_source) {
            return $existingOrder->fb_source;
        }

        return null;
    }

    /**
     * Miejsce konwersji (entry=…) — osobno od kampanii w fb_source.
     * Przy edycji zamówienia zachowaj istniejące conversion_placement, jeśli pole jest puste.
     */
    protected function resolveConversionPlacementForFormOrder(array $validated, int $courseId, ?FormOrder $existingOrder = null): ?string
    {
        $placement = app(\App\Services\OrderEntryPlacementService::class)->resolveForCourse(
            request(),
            $courseId,
            $validated['conversion_placement'] ?? null
        );

        if ($placement !== null) {
            return $placement;
        }

        if ($existingOrder && filled($existingOrder->conversion_placement)) {
            return $existingOrder->conversion_placement;
        }

        return null;
    }

    /**
     * Domyślne miejsce konwersji do prefill formularza: sesja (powiązana z kursem) → edycja zamówienia.
     */
    protected function resolveConversionPlacementDefaultForForm(int $courseId, ?FormOrder $existingOrder = null): ?string
    {
        $placement = app(\App\Services\OrderEntryPlacementService::class)->resolveForCourse(request(), $courseId);

        if ($placement !== null) {
            return $placement;
        }

        if ($existingOrder && filled($existingOrder->conversion_placement)) {
            return $existingOrder->conversion_placement;
        }

        return null;
    }

    /**
     * Zapisz zamówienie z odroczonym terminem płatności.
     */
    public function storeDeferredOrder(Request $request, $id)
    {
        $course = Course::with('priceVariants')->findOrFail($id);

        $rules = [
            'buyer_name' => 'required|string|max:500',
            'buyer_address' => 'required|string|max:500',
            'buyer_postcode' => 'required|string|max:50',
            'buyer_city' => 'required|string|max:255',
            'buyer_nip' => 'required|string|max:50',
            'recipient_name' => 'nullable|string|max:500',
            'recipient_address' => 'nullable|string|max:500',
            'recipient_postcode' => 'nullable|string|max:50',
            'recipient_city' => 'nullable|string|max:255',
            'recipient_nip' => 'nullable|string|max:50',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:50',
            'contact_email' => 'required|email|max:255',
            'invoice_notes' => 'nullable|string',
            'payment_terms' => 'required|integer|min:0|max:31',
            'fb_source' => 'nullable|string|max:255',
            'conversion_placement' => 'nullable|string|max:50',
        ];
        $this->addPriceVariantValidationRules($course, $rules);
        $this->mergeOrderFormParticipantValidationRules($rules, 'organisation');

        // Walidacja danych
        $validated = $request->validate($rules, array_merge([
            'buyer_name.required' => 'Nazwa nabywcy jest wymagana.',
            'buyer_address.required' => 'Adres jest wymagany.',
            'buyer_postcode.required' => 'Kod pocztowy jest wymagany.',
            'buyer_city.required' => 'Miasto jest wymagane.',
            'buyer_nip.required' => 'NIP jest wymagany.',
            'contact_name.required' => 'Nazwa/imię nazwisko jest wymagane.',
            'contact_phone.required' => 'Telefon kontaktowy jest wymagany.',
            'contact_email.required' => 'E-mail jest wymagany.',
            'contact_email.email' => 'Podaj prawidłowy adres e-mail.',
            'payment_terms.required' => 'Termin płatności jest wymagany.',
            'payment_terms.min' => 'Termin płatności musi być od 0 do 31 dni.',
            'payment_terms.max' => 'Termin płatności musi być od 0 do 31 dni.',
        ], $this->orderFormParticipantValidationMessages()));

        try {
            // Określ publigo_product_id - dla kursów z Publigo użyj id_old
            $publicoProductId = null;
            if ($course->source_id_old === 'certgen_Publigo' && $course->id_old) {
                $publicoProductId = $course->id_old;
            } elseif ($course->publigo_product_id) {
                $publicoProductId = $course->publigo_product_id;
            }

            $publigoPriceId = $this->resolvePubligoPriceIdForFormOrder($course, $publicoProductId);

            $coursePriceVariantId = $this->resolvedCoursePriceVariantId($course, $validated);

            // Sprawdź czy to edycja istniejącego zamówienia (w tym soft delete → restore przy zapisie)
            $checkoutResume = app(FormOrderCheckoutResumeService::class);
            $participantRows = $this->parseOrderFormParticipants($request, 'organisation');
            $primaryParticipant = $participantRows[0];
            $order = $checkoutResume->resolveForSubmit(
                (int) $id,
                $request->order_ident,
                $primaryParticipant['email'],
                $request->boolean('order_edit_intent')
            );

            if ($order && ! $checkoutResume->canUpdateFromFormSubmit($order)) {
                return redirect()
                    ->route('payment.deferred.edit', ['id' => $course->id, 'ident' => $order->ident])
                    ->with('error', 'To zamówienie zostało już zakończone lub zafakturowane. Zmiany nie zostały zapisane.');
            }

            $this->cancelSupersededOnlineOrdersBeforeDeferredSubmit(
                (int) $course->id,
                $participantRows,
                $order?->id
            );

            $this->assertOrderFormParticipantEmails((int) $course->id, $participantRows, $order?->id);
            $this->applyPrimaryParticipantToValidated($validated, $primaryParticipant);
            $currentPrice = $this->orderFormTotalPrice($course, $coursePriceVariantId, count($participantRows));

            // Dane do zapisania (uczestnik wyłącznie w form_order_participants)
            $orderData = [
                'ptw' => $validated['payment_terms'],
                'product_id' => $course->id,
                'product_name' => $course->title,
                'product_price' => $currentPrice,
                'product_description' => strip_tags($course->description ?? ''),
                'publigo_product_id' => $publicoProductId,
                'publigo_price_id' => $publigoPriceId,
                'course_price_variant_id' => $coursePriceVariantId,
                'orderer_name' => $validated['contact_name'],
                'orderer_address' => $validated['buyer_address'],
                'orderer_postal_code' => $validated['buyer_postcode'],
                'orderer_city' => $validated['buyer_city'],
                'orderer_phone' => $validated['contact_phone'],
                'orderer_email' => $validated['contact_email'],
                'buyer_name' => $validated['buyer_name'],
                'buyer_address' => $validated['buyer_address'],
                'buyer_postal_code' => $validated['buyer_postcode'],
                'buyer_city' => $validated['buyer_city'],
                'buyer_nip' => $validated['buyer_nip'],
                'recipient_name' => $validated['recipient_name'],
                'recipient_address' => $validated['recipient_address'],
                'recipient_postal_code' => $validated['recipient_postcode'],
                'recipient_city' => $validated['recipient_city'],
                'recipient_nip' => $validated['recipient_nip'],
                'invoice_notes' => $validated['invoice_notes'],
                'invoice_payment_delay' => $validated['payment_terms'] ?? null,
                'payment_mode' => FormOrder::PAYMENT_MODE_DEFERRED_INVOICE,
                'payment_status' => FormOrder::PAYMENT_STATUS_SUBMITTED,
                'submission_source' => FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM,
                'order_form_variant' => $this->resolveOrderFormVariantForStorage($request),
                'ip_address' => $request->ip(),
                'fb_source' => $this->resolveFbSourceForFormOrder($validated, $order),
                'conversion_placement' => $this->resolveConversionPlacementForFormOrder($validated, (int) $id, $order),
            ];

            // Aktualizuj istniejące zamówienie lub utwórz nowe
            if ($order) {
                $order->update($orderData);
                Log::info('Deferred order updated', [
                    'order_id' => $order->id,
                    'ident' => $order->ident,
                    'course_id' => $course->id,
                    'participant_email' => $validated['participant_email'],
                ]);
            } else {
                $orderData['ident'] = FormOrder::generateIdent();
                $orderData['order_date'] = now('UTC');
                $orderData['publigo_sent'] = 0;
                $orderData['status_completed'] = 0;
                $order = FormOrder::create($orderData);
                Log::info('Deferred order created', [
                    'order_id' => $order->id,
                    'ident' => $order->ident,
                    'course_id' => $course->id,
                    'participant_email' => $validated['participant_email'],
                ]);
            }

            // Zapisz uczestników w form_order_participants
            app(OrderFormParticipantService::class)->sync($order, $participantRows);

            app(\App\Services\OrderEntryPlacementService::class)->clear($request);

            app(FormOrderCheckoutResumeService::class)->storeAfterSubmit(
                (int) $id,
                $order,
                $primaryParticipant['email']
            );

            // Przekierowanie do strony podsumowania z PDF
            return redirect()
                ->route('orders.summary', ['ident' => $order->ident])
                ->with('success', 'Zamówienie zostało złożone pomyślnie!')
                ->with('order_just_submitted', $order->ident);

        } catch (Exception $e) {
            Log::error('Error creating deferred order', [
                'error' => $e->getMessage(),
                'course_id' => $id,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Wystąpił błąd podczas składania zamówienia. Spróbuj ponownie.');
        }
    }

    /**
     * Zapisz zamówienie z nowego formularza (na razie deleguje do istniejącej logiki odroczonej).
     * Docelowo tu będzie rozgałęzienie: odroczone vs płatność online.
     */
    public function storeOrderFormV2(Request $request, $id, BackendAnalyticsTracker $backendAnalyticsTracker)
    {
        $displayOptions = \App\Models\PaymentDisplayOption::getForCoursePage();
        abort_unless($displayOptions['show_order_form_v2'] ?? false, 404);

        $request->merge(['form_variant' => 'v2']);

        return $this->storeOrderForm($request, $id, $backendAnalyticsTracker);
    }

    public function storeOrderForm(Request $request, $id, BackendAnalyticsTracker $backendAnalyticsTracker)
    {
        $course = Course::with('priceVariants')->findOrFail($id);

        $buyerType = $request->input('buyer_type', 'organisation');
        if (! in_array($buyerType, ['organisation', 'person'], true)) {
            $buyerType = 'organisation';
        }

        $paymentTermsMax = $request->routeIs('payment.order-form-v2.store') ? 30 : 31;

        $rules = [
            'buyer_type' => 'required|in:organisation,person',
            'payment_type' => 'required|in:deferred,online',

            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:50',
            'contact_email' => 'required|email|max:255',

            'buyer_address' => 'required|string|max:500',
            'buyer_postcode' => 'required|string|max:50',
            'buyer_city' => 'required|string|max:255',

            'recipient_name' => 'nullable|string|max:500',
            'recipient_address' => 'nullable|string|max:500',
            'recipient_postcode' => 'nullable|string|max:50',
            'recipient_city' => 'nullable|string|max:255',
            'recipient_nip' => 'nullable|string|max:50',
            'recipient_internal_id' => 'nullable|string|max:20',

            'invoice_notes' => 'nullable|string',
            'payment_terms' => 'nullable|integer|min:0|max:'.$paymentTermsMax,
            'payment_gateway' => 'nullable|in:payu,paynow',
            'fb_source' => 'nullable|string|max:255',
            'conversion_placement' => 'nullable|string|max:50',
        ];

        if ($buyerType === 'organisation') {
            $rules['buyer_name'] = 'required|string|max:500';
            $rules['buyer_nip'] = 'required|string|max:50';
        } else {
            // osoba fizyczna: bez nazwy nabywcy
            $rules['buyer_name'] = 'nullable|string|max:500';
            $rules['buyer_nip'] = 'nullable|string|max:50';
            $rules['buyer_person_first_name'] = 'required|string|max:255';
            $rules['buyer_person_last_name'] = 'required|string|max:255';
        }

        $this->addPriceVariantValidationRules($course, $rules);
        $this->mergeOrderFormParticipantValidationRules($rules, $buyerType);

        $backendAnalyticsTracker->trackOrderFormSubmitAttempted($request, $course);

        try {
            $validated = $request->validate($rules, array_merge([
                'buyer_type.required' => 'Wybierz, jako kto zamawiasz.',
                'buyer_type.in' => 'Wybierz prawidłową opcję.',
                'payment_type.required' => 'Wybierz sposób rozliczenia.',
                'payment_type.in' => 'Wybierz prawidłowy sposób rozliczenia.',
                'payment_terms.max' => sprintf('Termin płatności nie może przekraczać %d dni.', $paymentTermsMax),
            ], $this->orderFormParticipantValidationMessages()));
        } catch (ValidationException $e) {
            $backendAnalyticsTracker->trackOrderFormValidationFailed(
                request: $request,
                course: $course,
                validationException: $e,
                context: 'laravel_validation'
            );

            throw $e;
        }

        // ODBIORCA: jeśli podano dane odbiorcy, wymagany NIP lub identyfikator wewnętrzny (KSeF)
        if ($buyerType === 'organisation') {
            $buyerNipForRecipient = preg_replace('/\D+/', '', (string) ($validated['buyer_nip'] ?? ''));
            $recipientIdentityError = app(OrderFormRecipientIdentityService::class)
                ->validateRecipientIdentity($request, $buyerNipForRecipient !== '' ? $buyerNipForRecipient : null);

            if ($recipientIdentityError !== null) {
                $backendAnalyticsTracker->trackOrderFormManualValidationFailed(
                    request: $request,
                    course: $course,
                    fieldKeys: [$recipientIdentityError['field']],
                    context: 'manual_recipient_identity'
                );

                return back()
                    ->withErrors([$recipientIdentityError['field'] => $recipientIdentityError['message']])
                    ->withInput();
            }
        }

        // Dodatkowa walidacja: termin płatności wymagany tylko dla faktury z odroczonym terminem
        if (($validated['payment_type'] ?? null) === 'deferred' && (! isset($validated['payment_terms']) || $validated['payment_terms'] === '')) {
            $backendAnalyticsTracker->trackOrderFormManualValidationFailed(
                request: $request,
                course: $course,
                fieldKeys: ['payment_terms'],
                context: 'manual_payment_terms'
            );

            return back()
                ->withErrors(['payment_terms' => sprintf(
                    'Podaj termin płatności dla faktury z odroczonym terminem (0–%d dni).',
                    $paymentTermsMax
                )])
                ->withInput();
        }

        if (($validated['payment_type'] ?? null) === 'online' && empty($validated['payment_gateway'])) {
            $backendAnalyticsTracker->trackOrderFormManualValidationFailed(
                request: $request,
                course: $course,
                fieldKeys: ['payment_gateway'],
                context: 'manual_payment_gateway'
            );

            return back()
                ->withErrors(['payment_gateway' => 'Wybierz bramkę płatności.'])
                ->withInput();
        }

        $participantRows = $this->parseOrderFormParticipants($request, $buyerType);
        $this->applyPrimaryParticipantToValidated($validated, $participantRows[0]);

        $coursePriceVariantId = $this->resolvedCoursePriceVariantId($course, $validated);

        // Płatność online – utwórz OnlinePaymentOrder i przekieruj do bramki
        if (($validated['payment_type'] ?? null) === 'online') {
            $backendAnalyticsTracker->trackOnlinePaymentSelected($request, $course, $validated['payment_gateway'] ?? null, $buyerType);

            return $this->processOrderFormOnlinePayment($request, $course, $validated, $buyerType, $coursePriceVariantId, $backendAnalyticsTracker, $participantRows);
        }

        $backendAnalyticsTracker->trackDeferredInvoiceSelected($request, $course, $buyerType);

        try {
            // Określ publigo_product_id - dla kursów z Publigo użyj id_old
            $publicoProductId = null;
            if ($course->source_id_old === 'certgen_Publigo' && $course->id_old) {
                $publicoProductId = $course->id_old;
            } elseif ($course->publigo_product_id) {
                $publicoProductId = $course->publigo_product_id;
            }

            $publigoPriceId = $this->resolvePubligoPriceIdForFormOrder($course, $publicoProductId);

            // Sprawdź czy to edycja istniejącego zamówienia (w tym soft delete → restore przy zapisie)
            $checkoutResume = app(FormOrderCheckoutResumeService::class);
            $order = $checkoutResume->resolveForSubmit(
                (int) $id,
                $request->order_ident,
                $validated['participant_email'],
                $request->boolean('order_edit_intent')
            );

            if ($order && ! $checkoutResume->canUpdateFromFormSubmit($order)) {
                return redirect()
                    ->route('payment.order-form.edit', ['id' => $course->id, 'ident' => $order->ident])
                    ->with('error', 'To zamówienie zostało już zakończone lub zafakturowane. Zmiany nie zostały zapisane.');
            }

            $this->cancelSupersededOnlineOrdersBeforeDeferredSubmit(
                (int) $course->id,
                $participantRows,
                $order?->id
            );

            $this->assertOrderFormParticipantEmails((int) $course->id, $participantRows, $order?->id);
            $currentPrice = $this->orderFormTotalPrice($course, $coursePriceVariantId, count($participantRows));

            $buyerName = $validated['buyer_name'] ?? null;
            $buyerNip = $buyerType === 'organisation' ? ($validated['buyer_nip'] ?? null) : null;
            if ($buyerType === 'person') {
                $buyerName = trim(($validated['buyer_person_first_name'] ?? '').' '.($validated['buyer_person_last_name'] ?? '')) ?: ($validated['contact_name'] ?? $buyerName);
                $buyerNip = null;
            }

            $orderData = [
                'ptw' => $validated['payment_terms'],
                'product_id' => $course->id,
                'product_name' => $course->title,
                'product_price' => $currentPrice,
                'product_description' => strip_tags($course->description ?? ''),
                'publigo_product_id' => $publicoProductId,
                'publigo_price_id' => $publigoPriceId,
                'course_price_variant_id' => $coursePriceVariantId,
                'orderer_name' => $validated['contact_name'],
                'orderer_address' => $validated['buyer_address'],
                'orderer_postal_code' => $validated['buyer_postcode'],
                'orderer_city' => $validated['buyer_city'],
                'orderer_phone' => $validated['contact_phone'],
                'orderer_email' => $validated['contact_email'],
                'buyer_name' => $buyerName,
                'buyer_address' => $validated['buyer_address'],
                'buyer_postal_code' => $validated['buyer_postcode'],
                'buyer_city' => $validated['buyer_city'],
                'buyer_nip' => $buyerNip,
                'invoice_notes' => $validated['invoice_notes'],
                'invoice_payment_delay' => $validated['payment_terms'] ?? null,
                'payment_mode' => FormOrder::PAYMENT_MODE_DEFERRED_INVOICE,
                'payment_status' => FormOrder::PAYMENT_STATUS_SUBMITTED,
                'submission_source' => FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM,
                'order_form_variant' => $this->resolveOrderFormVariantForStorage($request),
                'ip_address' => $request->ip(),
                'fb_source' => $this->resolveFbSourceForFormOrder($validated, $order),
                'conversion_placement' => $this->resolveConversionPlacementForFormOrder($validated, (int) $id, $order),
            ];

            $orderData = array_merge(
                $orderData,
                $buyerType === 'organisation'
                    ? $this->recipientFieldsForOrganisationOrder($request, $buyerNip)
                    : $this->clearRecipientFieldsForOrder()
            );

            if ($order) {
                $order->update($orderData);
            } else {
                $orderData['ident'] = FormOrder::generateIdent();
                $orderData['order_date'] = now('UTC');
                $orderData['publigo_sent'] = 0;
                $orderData['status_completed'] = 0;
                $order = FormOrder::create($orderData);
            }

            // Zapisz uczestników w form_order_participants
            app(OrderFormParticipantService::class)->sync($order, $participantRows);

            $this->subscribeOrderFormContactsToSendyIfConfigured($course, $validated, $participantRows);

            app(\App\Services\OrderEntryPlacementService::class)->clear($request);

            app(FormOrderCheckoutResumeService::class)->storeAfterSubmit(
                (int) $id,
                $order,
                $validated['participant_email']
            );

            $backendAnalyticsTracker->trackFormOrderCreated($request, $course, $order, [
                'order_flow' => 'deferred',
                'buyer_type' => $buyerType,
            ]);

            return redirect()
                ->route('orders.summary', ['ident' => $order->ident])
                ->with('success', 'Zamówienie zostało złożone pomyślnie!')
                ->with('order_just_submitted', $order->ident);
        } catch (Exception $e) {
            Log::error('Error creating order (order-form)', [
                'error' => $e->getMessage(),
                'course_id' => $id,
                'buyer_type' => $buyerType,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Wystąpił błąd podczas składania zamówienia. Spróbuj ponownie.');
        }
    }

    /**
     * Przetwórz płatność online z formularza order-form – zapis FormOrder + uczestnicy,
     * OnlinePaymentOrder (powiązanie) i przekierowanie do bramki.
     */
    protected function processOrderFormOnlinePayment(Request $request, Course $course, array $validated, string $buyerType, ?int $coursePriceVariantId, BackendAnalyticsTracker $backendAnalyticsTracker, array $participantRows = [])
    {
        if ($participantRows === []) {
            $participantRows = $this->parseOrderFormParticipants($request, $buyerType);
            $this->applyPrimaryParticipantToValidated($validated, $participantRows[0]);
        }

        $totalAmount = $this->resolveOnlineCheckoutAmount(
            (float) ($this->orderFormTotalPrice($course, $coursePriceVariantId, count($participantRows)) ?? 0)
        );

        if ($totalAmount <= 0) {
            return redirect()->route('payment.order-form', $course->id)
                ->with('error', 'To szkolenie nie ma ustawionej ceny. Skontaktuj się z organizatorem lub wybierz formularz zamówienia z odroczonym terminem płatności.')
                ->withInput();
        }

        $firstName = $validated['participant_first_name'];
        $lastName = $validated['participant_last_name'];
        $email = $validated['participant_email'];
        $phone = $validated['contact_phone'];

        $addressData = $this->collectOrderFormAddressData($request, $buyerType);
        $formData = $request->except(['_token']);
        $paymentGateway = $validated['payment_gateway'] ?? 'payu';
        $formOrder = null;

        try {
            $publicoProductId = null;
            if ($course->source_id_old === 'certgen_Publigo' && $course->id_old) {
                $publicoProductId = $course->id_old;
            } elseif ($course->publigo_product_id) {
                $publicoProductId = $course->publigo_product_id;
            }

            $publigoPriceId = $this->resolvePubligoPriceIdForFormOrder($course, $publicoProductId);
            $currentPrice = $totalAmount;

            $checkoutResume = app(FormOrderCheckoutResumeService::class);
            $formOrder = $checkoutResume->resolveForSubmit(
                (int) $course->id,
                $request->order_ident,
                $validated['participant_email'],
                $request->boolean('order_edit_intent')
            );

            if ($formOrder && ! $checkoutResume->canUpdateFromFormSubmit($formOrder)) {
                return redirect()
                    ->route('payment.order-form.edit', ['id' => $course->id, 'ident' => $formOrder->ident])
                    ->with('error', 'To zamówienie zostało już zakończone lub zafakturowane. Zmiany nie zostały zapisane.');
            }

            $this->assertOrderFormParticipantEmails((int) $course->id, $participantRows, $formOrder?->id);

            $buyerName = $validated['buyer_name'] ?? null;
            $buyerNip = $buyerType === 'organisation' ? ($validated['buyer_nip'] ?? null) : null;
            if ($buyerType === 'person') {
                $buyerName = trim(($validated['buyer_person_first_name'] ?? '').' '.($validated['buyer_person_last_name'] ?? '')) ?: ($validated['contact_name'] ?? $buyerName);
                $buyerNip = null;
            }

            $orderData = [
                'ptw' => null,
                'product_id' => $course->id,
                'product_name' => $course->title,
                'product_price' => $currentPrice,
                'product_description' => strip_tags($course->description ?? ''),
                'publigo_product_id' => $publicoProductId,
                'publigo_price_id' => $publigoPriceId,
                'course_price_variant_id' => $coursePriceVariantId,
                'orderer_name' => $validated['contact_name'],
                'orderer_address' => $validated['buyer_address'],
                'orderer_postal_code' => $validated['buyer_postcode'],
                'orderer_city' => $validated['buyer_city'],
                'orderer_phone' => $validated['contact_phone'],
                'orderer_email' => $validated['contact_email'],
                'buyer_name' => $buyerName,
                'buyer_address' => $validated['buyer_address'],
                'buyer_postal_code' => $validated['buyer_postcode'],
                'buyer_city' => $validated['buyer_city'],
                'buyer_nip' => $buyerNip,
                'invoice_notes' => $validated['invoice_notes'],
                'invoice_payment_delay' => null,
                'payment_mode' => FormOrder::PAYMENT_MODE_ONLINE_GATEWAY,
                'payment_status' => FormOrder::PAYMENT_STATUS_AWAITING_PAYMENT,
                'submission_source' => FormOrder::SUBMISSION_SOURCE_PNEDU_ORDER_FORM,
                'order_form_variant' => $this->resolveOrderFormVariantForStorage($request),
                'ip_address' => $request->ip(),
                'fb_source' => $this->resolveFbSourceForFormOrder($validated, $formOrder),
                'conversion_placement' => $this->resolveConversionPlacementForFormOrder($validated, (int) $course->id, $formOrder),
            ];

            $orderData = array_merge(
                $orderData,
                $buyerType === 'organisation'
                    ? $this->recipientFieldsForOrganisationOrder($request, $buyerNip)
                    : $this->clearRecipientFieldsForOrder()
            );

            if ($formOrder) {
                $formOrder->update($orderData);
            } else {
                $orderData['ident'] = FormOrder::generateIdent();
                $orderData['order_date'] = now('UTC');
                $orderData['publigo_sent'] = 0;
                $orderData['status_completed'] = 0;
                $formOrder = FormOrder::create($orderData);
            }

            app(OrderFormParticipantService::class)->sync($formOrder, $participantRows);

            $this->subscribeOrderFormContactsToSendyIfConfigured($course, $validated, $participantRows);

            app(\App\Services\OrderEntryPlacementService::class)->clear($request);

            $backendAnalyticsTracker->trackFormOrderCreated($request, $course, $formOrder, [
                'order_flow' => 'online',
                'buyer_type' => $buyerType,
            ]);

            $onlineOrder = \App\Models\OnlinePaymentOrder::create([
                'form_order_id' => $formOrder->id,
                'ident' => \App\Models\OnlinePaymentOrder::generateIdent(),
                'course_id' => $course->id,
                'payment_gateway' => $paymentGateway,
                'status' => \App\Models\OnlinePaymentOrder::STATUS_PENDING,
                'total_amount' => $totalAmount,
                'currency' => 'PLN',
                'buyer_type' => $buyerType === 'organisation' ? 'organisation' : 'person',
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'order_comment' => $validated['invoice_notes'] ?? null,
                'address_data' => $addressData,
                'form_data' => $formData,
                'ip_address' => $request->ip(),
            ]);

            $backendAnalyticsTracker->trackPaymentOrderCreated($request, $course, $formOrder, $onlineOrder, $buyerType);

            app(FormOrderCheckoutResumeService::class)->storeAfterSubmit(
                (int) $course->id,
                $formOrder,
                $validated['participant_email']
            );

            try {
                app(\App\Services\FormOrderOnlinePaymentRetryService::class)
                    ->sendPaymentStartedMail($formOrder, $course, $onlineOrder);
            } catch (\Throwable $mailException) {
                Log::error('Error sending online payment started email', [
                    'form_order_id' => $formOrder->id,
                    'online_payment_order_id' => $onlineOrder->id,
                    'error' => $mailException->getMessage(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error creating FormOrder/OnlinePaymentOrder (order-form online)', [
                'error' => $e->getMessage(),
                'course_id' => $course->id,
                'buyer_type' => $buyerType,
            ]);

            $input = $request->except('_token');
            if ($formOrder !== null) {
                $input['order_ident'] = $formOrder->ident;
            }

            return back()
                ->withInput($input)
                ->with('error', 'Wystąpił błąd podczas przygotowania płatności. Spróbuj ponownie.');
        }

        if ($paymentGateway === 'payu') {
            $payuService = $this->makePayUService();
            $notifyUrl = route('payment.payu.notify');
            $continueUrl = route('payment.payu.return');
            $result = $payuService->createOrder($onlineOrder, $notifyUrl, $continueUrl);

            if (! $result['success']) {
                $errorMsg = $result['error'] ?? 'Nie udało się połączyć z PayU. Spróbuj ponownie.';

                return redirect()->route('payment.order-form', $course->id)
                    ->with('error', $errorMsg)
                    ->withInput(array_merge($request->except('_token'), ['order_ident' => $formOrder->ident]));
            }
            session(['payu_order_ident' => $onlineOrder->ident]);
            session(['payu_order_email' => $onlineOrder->email]);

            return redirect()->away($result['redirect_uri']);
        }

        if ($paymentGateway === 'paynow') {
            $paynowService = $this->makePayNowService();
            $notifyUrl = route('payment.paynow.notify');
            $continueUrl = route('payment.paynow.return');
            $result = $paynowService->createOrder($onlineOrder, $notifyUrl, $continueUrl);

            if (! $result['success']) {
                $errorMsg = $result['error'] ?? 'Nie udało się połączyć z PayNow. Spróbuj ponownie.';

                return redirect()->route('payment.order-form', $course->id)
                    ->with('error', $errorMsg)
                    ->withInput(array_merge($request->except('_token'), ['order_ident' => $formOrder->ident]));
            }

            return redirect()->away($result['redirect_url']);
        }

        return redirect()->route('payment.order-form', $course->id)
            ->with('error', 'Nieznana bramka płatności.')
            ->withInput(array_merge($request->except('_token'), ['order_ident' => $formOrder->ident]));
    }

    /**
     * Zapis zamawiającego i (jeśli inny e-mail) uczestnika na listę Sendy przypisaną do kursu.
     */
    protected function subscribeOrderFormContactsToSendyIfConfigured(Course $course, array $validated, array $participantRows = []): void
    {
        if (trim((string) ($course->sendy_suppression_list_id ?? '')) === '') {
            return;
        }

        $sendy = SendyService::fromConfig();
        if (! $sendy) {
            Log::warning('Sendy: brak SENDY_URL / SENDY_API_KEY – pominięto zapis z zamówienia', ['course_id' => $course->id]);

            return;
        }

        try {
            $sendy->subscribeOrderFormContacts($course, $validated);
        } catch (\Throwable $e) {
            Log::error('Sendy order-form subscribe exception', [
                'course_id' => $course->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Zbierz dane adresowe z formularza order-form.
     */
    protected function collectOrderFormAddressData(Request $request, string $buyerType): array
    {
        if ($buyerType === 'organisation') {
            $buyerNip = preg_replace('/\D+/', '', (string) $request->input('buyer_nip', ''));
            $identity = app(OrderFormRecipientIdentityService::class)->resolveStoragePayload(
                $request,
                $buyerNip !== '' ? $buyerNip : null
            );

            return [
                'buyer' => [
                    'nip' => $request->input('buyer_nip'),
                    'country' => 'Polska',
                    'name' => $request->input('buyer_name'),
                    'street' => $request->input('buyer_address'),
                    'building_no' => '',
                    'flat_no' => '',
                    'postcode' => $request->input('buyer_postcode'),
                    'city' => $request->input('buyer_city'),
                ],
                'recipient' => [
                    'nip' => $identity['recipient_nip'],
                    'internal_id' => $identity['ksef_additional_entity_id_type'] === OrderFormRecipientIdentityService::KSEF_ID_TYPE_IDWEW
                        ? $identity['ksef_additional_entity_identifier']
                        : null,
                    'country' => 'Polska',
                    'name' => $request->input('recipient_name'),
                    'street' => $request->input('recipient_address'),
                    'building_no' => '',
                    'flat_no' => '',
                    'postcode' => $request->input('recipient_postcode'),
                    'city' => $request->input('recipient_city'),
                ],
            ];
        }

        // Osoba fizyczna
        $buyerName = trim(($request->input('buyer_person_first_name') ?? '').' '.($request->input('buyer_person_last_name') ?? ''));
        if (empty($buyerName)) {
            $buyerName = $request->input('contact_name');
        }

        return [
            'full_name' => $buyerName,
            'street' => $request->input('buyer_address'),
            'building_no' => '',
            'flat_no' => '',
            'postcode' => $request->input('buyer_postcode'),
            'city' => $request->input('buyer_city'),
            'country' => 'Polska',
        ];
    }

    /**
     * Wyświetl podsumowanie zamówienia z PDF.
     */
    public function orderSummary($ident)
    {
        $order = FormOrder::with(['primaryParticipant', 'participants' => fn ($q) => $q->orderBy('id')])
            ->where('ident', $ident)
            ->firstOrFail();
        $course = $order->course;

        // Wyślij e-mail z załączonym PDF tylko bezpośrednio po przesłaniu/edycji formularza (nie przy odświeżeniu strony)
        $shouldSendEmail = session('order_just_submitted') === $ident;
        if ($shouldSendEmail) {
            session()->forget('order_just_submitted');
        }

        try {
            if ($shouldSendEmail) {
                // Przygotuj listę adresów – główny odbiorca: zamawiający (orderer_email)
                $emailsToSend = [];

                // 1. Zamawiający – główny odbiorca (e-mail do faktury, wymagany w formularzu)
                $ordererEmail = $order->orderer_email;
                if ($ordererEmail) {
                    $emailsToSend[] = strtolower(trim($ordererEmail));
                }

                // 2. Wszyscy uczestnicy – jeśli e-mail inny niż już na liście
                foreach ($order->participants as $fop) {
                    $participantEmail = trim((string) ($fop->participant_email ?? ''));
                    if ($participantEmail === '') {
                        continue;
                    }
                    $normalizedParticipant = strtolower($participantEmail);
                    if (! in_array($normalizedParticipant, $emailsToSend, true)) {
                        $emailsToSend[] = $normalizedParticipant;
                    }
                }

                // 3. Kopia dla admina
                $adminEmail = 'waldemar.grabowski@hostnet.pl';
                if (! in_array(strtolower($adminEmail), $emailsToSend)) {
                    $emailsToSend[] = $adminEmail;
                }

                Log::info('Próba wysyłki e-maila z zamówieniem', [
                    'order_id' => $order->id,
                    'order_ident' => $order->ident,
                    'emails' => $emailsToSend,
                ]);

                // Wyślij e-mail na wszystkie adresy
                foreach ($emailsToSend as $email) {
                    try {
                        Mail::to($email)
                            ->send(new OrderNotificationMail($order, $course));

                        Log::info('E-mail z zamówieniem został wysłany', [
                            'order_id' => $order->id,
                            'order_ident' => $order->ident,
                            'email' => $email,
                        ]);
                    } catch (Exception $emailException) {
                        // Loguj błąd dla konkretnego adresu, ale kontynuuj wysyłkę na pozostałe
                        Log::error('Błąd wysyłki e-maila z zamówieniem na konkretny adres: '.$emailException->getMessage(), [
                            'order_id' => $order->id,
                            'order_ident' => $order->ident,
                            'email' => $email,
                            'exception' => $emailException->getTraceAsString(),
                        ]);
                    }
                }
            }

        } catch (Exception $e) {
            // Loguj błąd, ale nie blokuj wyświetlania podsumowania
            Log::error('Błąd wysyłki e-maila z zamówieniem: '.$e->getMessage(), [
                'order_id' => $order->id,
                'order_ident' => $order->ident,
                'exception' => $e->getTraceAsString(),
            ]);
        }

        $orderEditLocked = $order->isEditLocked();

        return view('orders.summary', compact('order', 'course', 'orderEditLocked'));
    }

    /**
     * Generuj PDF z zamówieniem.
     */
    public function orderPdf($ident)
    {
        $order = FormOrder::with(['primaryParticipant', 'participants' => fn ($q) => $q->orderBy('id')])
            ->where('ident', $ident)
            ->firstOrFail();
        $course = $order->course;

        $pdf = Pdf::loadView('orders.pdf', [
            'order' => $order,
            'course' => $course,
            'brandPublicUrl' => config('mail.brand.public_url'),
            'brandPublicLabel' => config('mail.brand.public_label'),
            'contactEmail' => config('mail.system.reply_to_address'),
        ]);

        return $pdf->stream('zamowienie-'.$order->ident.'.pdf');
    }

    /**
     * Aktywne warianty cenowe posortowane po ID (rosnąco) — domyślny wybór na stronie kursu to pierwszy z listy (najniższe ID).
     *
     * @return \Illuminate\Support\Collection<int, CoursePriceVariant>
     */
    protected function activePriceVariantsOrdered(Course $course): \Illuminate\Support\Collection
    {
        $courseEnded = $course->hasEnded();

        return $course->priceVariants
            ->filter(fn ($v) => (bool) $v->is_active)
            ->filter(fn ($v) => $v->isAvailableForCourseEndState($courseEnded))
            ->sortBy(fn ($v) => (int) $v->id)
            ->values();
    }

    /**
     * Reguły walidacji pola price_variant_id (wiele wariantów — wybór obowiązkowy).
     */
    protected function addPriceVariantValidationRules(Course $course, array &$rules): void
    {
        $count = $this->activePriceVariantsOrdered($course)->count();
        $variantExistsOnPneadm = function (mixed $value) use ($course): bool {
            if ($value === null || $value === '' || ! is_numeric($value)) {
                return false;
            }

            return $this->priceVariantExistsActiveForCourse($course, (int) $value);
        };

        if ($count > 1) {
            $rules['price_variant_id'] = [
                'required',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($course, $variantExistsOnPneadm): void {
                    if ($variantExistsOnPneadm($value)) {
                        return;
                    }
                    // Po zmianie szkolenia w adm może przyjść stare ID — spróbuj rematchu (nazwa / pierwszy).
                    $preferred = is_numeric($value) ? (int) $value : null;
                    if ($this->coercePriceVariantIdForCourse($course, $preferred) !== null) {
                        return;
                    }
                    $fail('Wybierz prawidłowy wariant cenowy dla tego szkolenia.');
                },
            ];
        } elseif ($count === 1) {
            // Jedyny wariant: nie blokuj zapisu przy starym ID z poprzedniego szkolenia (rematch w resolved*).
            $rules['price_variant_id'] = [
                'nullable',
                'integer',
            ];
        }
    }

    /**
     * ID wariantu cenowego do zapisu na zamówieniu (null gdy brak aktywnych wariantów).
     */
    protected function resolvedCoursePriceVariantId(Course $course, array $validated): ?int
    {
        $raw = $validated['price_variant_id'] ?? null;
        $preferred = ($raw !== null && $raw !== '' && is_numeric($raw)) ? (int) $raw : null;

        return $this->coercePriceVariantIdForCourse($course, $preferred);
    }

    /**
     * Kwota produktu na zamówieniu wg wybranego wariantu lub najtańszego aktywnego ({@see Course::getCurrentPrice()}).
     *
     * @return float|null
     */
    protected function productPriceForFormOrder(Course $course, ?int $coursePriceVariantId)
    {
        if ($coursePriceVariantId === null) {
            $info = $course->getCurrentPrice();

            return $info['price'] ?? null;
        }
        $variant = CoursePriceVariant::query()
            ->where('id', $coursePriceVariantId)
            ->where('course_id', $course->id)
            ->first();
        if (! $variant) {
            $info = $course->getCurrentPrice();

            return $info['price'] ?? null;
        }

        return round($variant->getCurrentPrice(), 2);
    }

    /**
     * Czy wariant jest aktywny i należy do kursu (baza pneadm).
     */
    protected function priceVariantExistsActiveForCourse(Course $course, int $variantId): bool
    {
        $variant = CoursePriceVariant::query()
            ->where('id', $variantId)
            ->where('course_id', $course->id)
            ->where('is_active', true)
            ->first();

        return $variant !== null && $variant->isAvailableForCourseEndState($course->hasEnded());
    }

    /**
     * Przy wielu wariantach: wymaga ?price_variant_id= z wyboru na stronie kursu.
     */
    protected function enforcePriceVariantFromQueryOrRedirect(Course $course, ?string $ident): ?\Illuminate\Http\RedirectResponse
    {
        if ($ident) {
            return null;
        }
        $variants = $this->activePriceVariantsOrdered($course);
        if ($variants->count() <= 1) {
            return null;
        }
        $qv = request()->query('price_variant_id');
        if ($qv === null || $qv === '' || ! is_numeric($qv)) {
            return redirect()->route('courses.show', $course->id)
                ->with('error', 'Wybierz wariant cenowy na stronie szkolenia, a następnie otwórz formularz zamówienia.');
        }
        if (! $this->priceVariantExistsActiveForCourse($course, (int) $qv)) {
            return redirect()->route('courses.show', $course->id)
                ->with('error', 'Nieprawidłowy wariant cenowy. Wybierz wariant ponownie na stronie szkolenia.');
        }

        return null;
    }

    /**
     * ID wariantu do ukrytego pola formularza (nowe zamówienie lub edycja z rematchiem po zmianie szkolenia).
     */
    protected function prefillPriceVariantIdForPublicOrderForm(Course $course, ?string $ident, array $orderData): ?int
    {
        if ($ident) {
            $v = $orderData['price_variant_id'] ?? null;
            $preferred = ($v !== null && $v !== '' && is_numeric($v)) ? (int) $v : null;

            return $this->coercePriceVariantIdForCourse($course, $preferred);
        }
        $variants = $this->activePriceVariantsOrdered($course);
        if ($variants->isEmpty()) {
            return null;
        }
        if ($variants->count() === 1) {
            return (int) $variants->first()->id;
        }

        $qv = request()->query('price_variant_id');

        return ($qv !== null && $qv !== '' && is_numeric($qv)) ? (int) $qv : null;
    }

    /**
     * Dopasuj price_variant_id do aktualnego szkolenia (ważne po zmianie kursu w adm).
     *
     * Kolejność: poprawne ID → ten sam name na nowym kursie → pierwszy aktywny (gdy było stare ID)
     * → jedyny aktywny → null przy wielu wariantach bez preferencji.
     */
    protected function coercePriceVariantIdForCourse(Course $course, ?int $preferredId): ?int
    {
        $variants = $this->activePriceVariantsOrdered($course);
        if ($variants->isEmpty()) {
            return null;
        }

        if ($preferredId !== null && $this->priceVariantExistsActiveForCourse($course, $preferredId)) {
            return $preferredId;
        }

        if ($preferredId !== null) {
            $old = CoursePriceVariant::query()->find($preferredId);
            if ($old && filled($old->name)) {
                $needle = mb_strtolower(trim((string) $old->name));
                $byName = $variants->first(
                    fn ($v) => mb_strtolower(trim((string) $v->name)) === $needle
                );
                if ($byName) {
                    return (int) $byName->id;
                }
            }

            return (int) $variants->first()->id;
        }

        if ($variants->count() === 1) {
            return (int) $variants->first()->id;
        }

        return null;
    }

    /**
     * ID ceny Publigo zapisywane w form_orders.
     *
     * Formularz na pnedu bierze wartości z rekordu kursu. Często publigo_product_id jest (np. z id_old / certgen),
     * a publigo_price_id w tabeli courses pozostaje puste — wtedy w adm nie pojawia się „Dodaj zamówienie PUBLIGO”.
     * Stary formularz (zdalna-lekcja) zwykle zakładał domyślną cenę. Używamy 1 jak w {@see Course::getPubligoPaymentUrl()}.
     */
    protected function resolvePubligoPriceIdForFormOrder(Course $course, ?int $publigoProductId): ?int
    {
        if ($publigoProductId === null || $publigoProductId === 0) {
            return filled($course->publigo_price_id) ? (int) $course->publigo_price_id : null;
        }

        if (filled($course->publigo_price_id)) {
            return (int) $course->publigo_price_id;
        }

        return 1;
    }

    /**
     * Pola odbiorcy + metadane KSeF Podmiot3 dla zamówienia instytucji/firmy.
     *
     * @return array<string, mixed>
     */
    protected function recipientFieldsForOrganisationOrder(Request $request, ?string $buyerNip): array
    {
        $resolved = app(OrderFormRecipientIdentityService::class)->resolveStoragePayload($request, $buyerNip);

        return [
            'recipient_name' => $request->input('recipient_name'),
            'recipient_address' => $request->input('recipient_address'),
            'recipient_postal_code' => $request->input('recipient_postcode'),
            'recipient_city' => $request->input('recipient_city'),
            'recipient_nip' => $resolved['recipient_nip'],
            'ksef_entity_source' => $resolved['ksef_entity_source'],
            'ksef_additional_entity_role' => $resolved['ksef_additional_entity_role'],
            'ksef_additional_entity_id_type' => $resolved['ksef_additional_entity_id_type'],
            'ksef_additional_entity_identifier' => $resolved['ksef_additional_entity_identifier'],
        ];
    }

    /**
     * @return array<string, null|string>
     */
    protected function clearRecipientFieldsForOrder(): array
    {
        return [
            'recipient_name' => null,
            'recipient_address' => null,
            'recipient_postal_code' => null,
            'recipient_city' => null,
            'recipient_nip' => null,
            'ksef_entity_source' => OrderFormRecipientIdentityService::KSEF_SOURCE_NONE,
            'ksef_additional_entity_role' => null,
            'ksef_additional_entity_id_type' => null,
            'ksef_additional_entity_identifier' => null,
        ];
    }

    protected function resolveOrderFormVariantForStorage(Request $request): string
    {
        if ($request->routeIs('payment.order-form-v2.store')) {
            return OrderFormVariant::V2;
        }

        $fromInput = $request->input('form_variant');
        if (is_string($fromInput) && $fromInput !== '') {
            return OrderFormVariant::normalize($fromInput);
        }

        return OrderFormVariant::LEGACY;
    }

    protected function resolveOnlineCheckoutAmount(float $normalAmount): float
    {
        return DeveloperOnlinePaymentTest::resolveCheckoutAmount(
            $normalAmount,
            PaymentDisplayOption::getForCoursePage(),
            auth()->user()
        );
    }

    protected function makePayUService(): \App\Services\PayUService
    {
        return new \App\Services\PayUService(
            DeveloperOnlinePaymentTest::sandboxGatewayOverride(
                PaymentDisplayOption::getForCoursePage(),
                auth()->user()
            )
        );
    }

    protected function makePayNowService(): \App\Services\PayNowService
    {
        return new \App\Services\PayNowService(
            DeveloperOnlinePaymentTest::sandboxGatewayOverride(
                PaymentDisplayOption::getForCoursePage(),
                auth()->user()
            )
        );
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    protected function mergeOrderFormParticipantValidationRules(array &$rules, string $buyerType): void
    {
        $rules = array_merge($rules, app(OrderFormParticipantService::class)->validationRules($buyerType));
    }

    /**
     * @return array<string, string>
     */
    protected function orderFormParticipantValidationMessages(): array
    {
        return app(OrderFormParticipantService::class)->validationMessages();
    }

    /**
     * @return list<array{first_name: string, last_name: string, email: string}>
     */
    protected function parseOrderFormParticipants(Request $request, string $buyerType): array
    {
        $rows = app(OrderFormParticipantService::class)->parseFromRequest($request, $buyerType);
        if ($rows === []) {
            throw ValidationException::withMessages([
                'participant_email' => 'Podaj dane przynajmniej jednego uczestnika szkolenia.',
            ]);
        }

        foreach ($rows as $index => $row) {
            if ($row['first_name'] === '' || $row['last_name'] === '' || $row['email'] === '') {
                $prefix = $index === 0 ? 'participant_' : 'participants.'.$index.'.';
                $messages = [];
                if ($row['first_name'] === '') {
                    $messages[$index === 0 ? 'participant_first_name' : $prefix.'first_name'] = 'Imię uczestnika jest wymagane.';
                }
                if ($row['last_name'] === '') {
                    $messages[$index === 0 ? 'participant_last_name' : $prefix.'last_name'] = 'Nazwisko uczestnika jest wymagane.';
                }
                if ($row['email'] === '') {
                    $messages[$index === 0 ? 'participant_email' : $prefix.'email'] = 'E-mail uczestnika jest wymagany.';
                }
                throw ValidationException::withMessages($messages);
            }
        }

        return $rows;
    }

    /**
     * @param  list<array{first_name: string, last_name: string, email: string}>  $rows
     */
    protected function cancelSupersededOnlineOrdersBeforeDeferredSubmit(
        int $courseId,
        array $participantRows,
        ?int $exceptFormOrderId
    ): void {
        $emails = array_map(
            static fn (array $row): string => (string) ($row['email'] ?? ''),
            $participantRows
        );

        app(FormOrderOnlineAbandonmentService::class)->cancelSupersededUnpaidOnlineOrders(
            $courseId,
            $emails,
            $exceptFormOrderId
        );
    }

    /**
     * @param  list<array{first_name: string, last_name: string, email: string}>  $rows
     */
    protected function assertOrderFormParticipantEmails(int $courseId, array $rows, ?int $exceptFormOrderId): void
    {
        app(OrderFormParticipantService::class)->assertEmailsAvailable($courseId, $rows, $exceptFormOrderId);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array{first_name: string, last_name: string, email: string}  $primary
     */
    protected function applyPrimaryParticipantToValidated(array &$validated, array $primary): void
    {
        $validated['participant_first_name'] = $primary['first_name'];
        $validated['participant_last_name'] = $primary['last_name'];
        $validated['participant_email'] = $primary['email'];
    }

    protected function orderFormTotalPrice(Course $course, ?int $coursePriceVariantId, int $participantCount): ?float
    {
        $unit = $this->productPriceForFormOrder($course, $coursePriceVariantId);

        return app(OrderFormParticipantService::class)->totalPrice(
            $unit !== null ? (float) $unit : null,
            $participantCount
        );
    }
}
