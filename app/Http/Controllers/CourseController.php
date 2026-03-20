<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\FormOrder;
use App\Models\FormOrderParticipant;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\OrderNotificationMail;
use App\Services\SendyService;

class CourseController extends Controller
{
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

                if (!empty($searchQuery)) {
                    $coursesQuery->where(function($q) use ($searchQuery) {
                        $q->where('title', 'like', '%' . $searchQuery . '%')
                          ->orWhere('description', 'like', '%' . $searchQuery . '%');
                    });
                }

                // Sortowanie według daty rozpoczęcia
                $courses = $coursesQuery
                    ->orderBy('start_date', $sort)
                    ->paginate(20)
                    ->appends([
                        'sort' => $sort,
                        'q' => $searchQuery
                    ]);
            }

            // Sprawdź uczestnictwo dla zalogowanego użytkownika
            $userEmail = auth()->check() ? auth()->user()->email : null;
            $participantCourseIds = [];
            $participantIdsByCourse = []; // Mapowanie course_id => participant_id
            
            if ($userEmail) {
                try {
                    $participants = DB::connection('pneadm')
                        ->table('participants')
                        ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($userEmail))])
                        ->select('id', 'course_id')
                        ->get();
                    
                    $participantCourseIds = $participants->pluck('course_id')->toArray();
                    $participantIdsByCourse = $participants->pluck('id', 'course_id')->toArray();
                } catch (Exception $e) {
                    Log::warning('Error checking participants: ' . $e->getMessage());
                }
            }

            $pageTitle = 'TIK w pracy NAUCZYCIELA';
            return view('courses.free', compact('courses', 'sort', 'searchQuery', 'participantCourseIds', 'participantIdsByCourse', 'pageTitle'));
        } catch (Exception $e) {
            Log::error('Error accessing free courses: ' . $e->getMessage());
            
            return view('courses.free', [
                'courses' => collect([]),
                'databaseError' => true,
                'pageTitle' => 'TIK w pracy NAUCZYCIELA'
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

                if (!empty($searchQuery)) {
                    $coursesQuery->where(function($q) use ($searchQuery) {
                        $q->where('title', 'like', '%' . $searchQuery . '%')
                          ->orWhere('description', 'like', '%' . $searchQuery . '%');
                    });
                }

                // Sortowanie według daty rozpoczęcia
                $courses = $coursesQuery
                    ->orderBy('start_date', $sort)
                    ->paginate(20)
                    ->appends([
                        'sort' => $sort,
                        'q' => $searchQuery
                    ]);
            }

            // Sprawdź uczestnictwo dla zalogowanego użytkownika
            $userEmail = auth()->check() ? auth()->user()->email : null;
            $participantCourseIds = [];
            $participantIdsByCourse = []; // Mapowanie course_id => participant_id
            
            if ($userEmail) {
                try {
                    $participants = DB::connection('pneadm')
                        ->table('participants')
                        ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($userEmail))])
                        ->select('id', 'course_id')
                        ->get();
                    
                    $participantCourseIds = $participants->pluck('course_id')->toArray();
                    $participantIdsByCourse = $participants->pluck('id', 'course_id')->toArray();
                } catch (Exception $e) {
                    Log::warning('Error checking participants: ' . $e->getMessage());
                }
            }

            $pageTitle = 'Szkolny ADMINISTRATOR Office 365';
            return view('courses.free', compact('courses', 'sort', 'searchQuery', 'participantCourseIds', 'participantIdsByCourse', 'pageTitle'));
        } catch (Exception $e) {
            Log::error('Error accessing office365 courses: ' . $e->getMessage());
            
            return view('courses.free', [
                'courses' => collect([]),
                'databaseError' => true,
                'pageTitle' => 'Szkolny ADMINISTRATOR Office 365'
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

                if (!empty($searchQuery)) {
                    $coursesQuery->where(function($q) use ($searchQuery) {
                        $q->where('title', 'like', '%' . $searchQuery . '%')
                          ->orWhere('description', 'like', '%' . $searchQuery . '%');
                    });
                }

                // Sortowanie według daty rozpoczęcia
                $courses = $coursesQuery
                    ->orderBy('start_date', $sort)
                    ->paginate(20)
                    ->appends([
                        'sort' => $sort,
                        'q' => $searchQuery
                    ]);
            }

            // Sprawdź uczestnictwo dla zalogowanego użytkownika
            $userEmail = auth()->check() ? auth()->user()->email : null;
            $participantCourseIds = [];
            $participantIdsByCourse = []; // Mapowanie course_id => participant_id
            
            if ($userEmail) {
                try {
                    $participants = DB::connection('pneadm')
                        ->table('participants')
                        ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($userEmail))])
                        ->select('id', 'course_id')
                        ->get();
                    
                    $participantCourseIds = $participants->pluck('course_id')->toArray();
                    $participantIdsByCourse = $participants->pluck('id', 'course_id')->toArray();
                } catch (Exception $e) {
                    Log::warning('Error checking participants: ' . $e->getMessage());
                }
            }

            $pageTitle = 'Akademia Rodzica';
            return view('courses.free', compact('courses', 'sort', 'searchQuery', 'participantCourseIds', 'participantIdsByCourse', 'pageTitle'));
        } catch (Exception $e) {
            Log::error('Error accessing parent academy courses: ' . $e->getMessage());
            
            return view('courses.free', [
                'courses' => collect([]),
                'databaseError' => true,
                'pageTitle' => 'Akademia Rodzica'
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

                if (!empty($searchQuery)) {
                    $coursesQuery->where(function($q) use ($searchQuery) {
                        $q->where('title', 'like', '%' . $searchQuery . '%')
                          ->orWhere('description', 'like', '%' . $searchQuery . '%');
                    });
                }

                // Sortowanie według daty rozpoczęcia
                $courses = $coursesQuery
                    ->orderBy('start_date', $sort)
                    ->paginate(20)
                    ->appends([
                        'sort' => $sort,
                        'q' => $searchQuery
                    ]);
            }

            // Sprawdź uczestnictwo dla zalogowanego użytkownika
            $userEmail = auth()->check() ? auth()->user()->email : null;
            $participantCourseIds = [];
            $participantIdsByCourse = []; // Mapowanie course_id => participant_id
            
            if ($userEmail) {
                try {
                    $participants = DB::connection('pneadm')
                        ->table('participants')
                        ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($userEmail))])
                        ->select('id', 'course_id')
                        ->get();
                    
                    $participantCourseIds = $participants->pluck('course_id')->toArray();
                    $participantIdsByCourse = $participants->pluck('id', 'course_id')->toArray();
                } catch (Exception $e) {
                    Log::warning('Error checking participants: ' . $e->getMessage());
                }
            }

            $pageTitle = 'Akademia Dyrektora';
            return view('courses.free', compact('courses', 'sort', 'searchQuery', 'participantCourseIds', 'participantIdsByCourse', 'pageTitle'));
        } catch (Exception $e) {
            Log::error('Error accessing director academy courses: ' . $e->getMessage());
            
            return view('courses.free', [
                'courses' => collect([]),
                'databaseError' => true,
                'pageTitle' => 'Akademia Dyrektora'
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
        // Nadchodzące szkolenia
        $upcomingCourses = Course::with('priceVariants')
            ->where('is_active', true)
            ->where('type', 'online')
            ->where('is_paid', 1)
            ->where('start_date', '>', now())
            ->whereNull('deleted_at')
            ->where('source_id_old', 'certgen_Publigo')
            ->orderBy('start_date', 'asc')
            ->get();

        // Archiwalne szkolenia (zakończone)
        $archivedCourses = Course::with('priceVariants')
            ->where('is_active', true)
            ->where('type', 'online')
            ->where('is_paid', 1)
            ->whereNull('deleted_at')
            ->where(function($query) {
                $query->where(function($q) {
                    // Szkolenia z datą zakończenia w przeszłości
                    $q->whereNotNull('end_date')
                      ->where('end_date', '<', now());
                })->orWhere(function($q) {
                    // Szkolenia bez daty zakończenia, ale z datą rozpoczęcia w przeszłości (starsze niż 30 dni)
                    $q->whereNull('end_date')
                      ->where('start_date', '<', now()->subDays(30));
                });
            })
            ->where('source_id_old', 'certgen_Publigo')
            ->orderBy('start_date', 'desc')
            ->get();

        return view('courses.individual', compact('upcomingCourses', 'archivedCourses'));
    }

    /**
     * Wyświetl szczegóły szkolenia.
     */
    public function show($id)
    {
        $course = \App\Models\Course::with(['instructor', 'priceVariants', 'onlineDetail'])->findOrFail($id);
        
        // Debug: sprawdź czy pole offer_description_html istnieje
        \Log::info('Course data:', [
            'id' => $course->id,
            'title' => $course->title,
            'offer_description_html' => $course->offer_description_html ?? 'NULL',
            'has_offer_description' => !empty($course->offer_description_html),
            'trainer' => $course->trainer,
            'trainer_title' => $course->trainer_title,
            'instructor_id' => $course->instructor_id,
            'instructor_title' => $course->instructor->title ?? 'NULL',
            'instructor_full_name' => $course->instructor->full_name ?? 'NULL',
            'instructor_gender' => $course->instructor->gender ?? 'NULL',
            'instructor_bio_html' => $course->instructor->bio_html ?? 'NULL',
            'has_instructor_bio' => !empty($course->instructor->bio_html)
        ]);
        
        $paymentOptions = \App\Models\PaymentDisplayOption::getForCoursePage();
        return view('courses.show', compact('course', 'paymentOptions'));
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

        if (!$result['tik']) {
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
        return view('courses.pay-online', compact('course'));
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
            
            if ($hasRecipientData && !$request->filled('recipient_nip')) {
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
        $totalAmount = $priceInfo['price'] ?? 0;

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

        $payuService = new \App\Services\PayUService();
        $notifyUrl = route('payment.payu.notify');
        $continueUrl = route('payment.payu.return');

        $result = $payuService->createOrder($order, $notifyUrl, $continueUrl);

        if (!$result['success']) {
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
        $totalAmount = $priceInfo['price'] ?? 0;

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

        $paynowService = new \App\Services\PayNowService();
        $notifyUrl = route('payment.paynow.notify');
        $continueUrl = route('payment.paynow.return');

        $result = $paynowService->createOrder($order, $notifyUrl, $continueUrl);

        if (!$result['success']) {
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
        
        // Sprawdź czy to tryb testowy (URL kończy się na /test)
        $isTestMode = Str::endsWith(request()->path(), '/deferred-order/test');
        
        // Sprawdź czy to edycja istniejącego zamówienia
        $orderData = [];
        $isEditMode = false;
        
        if ($ident) {
            $existingOrder = FormOrder::where('ident', $ident)->first();
            if ($existingOrder && $existingOrder->product_id == $id) {
                $isEditMode = true;
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
                    'participant_first_name' => explode(' ', $existingOrder->participant_name)[0] ?? '',
                    'participant_last_name' => implode(' ', array_slice(explode(' ', $existingOrder->participant_name), 1)),
                    'participant_email' => $existingOrder->participant_email,
                    'invoice_notes' => $existingOrder->invoice_notes,
                    'payment_terms' => $existingOrder->invoice_payment_delay ?? $existingOrder->ptw,
                    'order_id' => $existingOrder->id,
                    'order_ident' => $existingOrder->ident,
                ];
            }
        }
        
        // Dane testowe (tylko jeśli nie ma danych z zamówienia)
        $testData = $orderData;
        if (empty($testData) && $isTestMode) {
            $testData = [
                'buyer_name' => 'Gmina Bieżuń',
                'buyer_address' => 'ul. Warszawska 5',
                'buyer_postcode' => '09-320',
                'buyer_city' => 'Bieżuń',
                'buyer_nip' => '5110265245',
                'buyer_nip8' => '5110265245',
                'recipient_name' => 'Szkoła Podstawowa im. Andrzeja Zamoyskiego',
                'recipient_address' => 'ul. Andrzeja Zamoyskiego 28',
                'recipient_postcode' => '09-320',
                'recipient_city' => 'Bieżuń',
                'recipient_nip8' => '5261040828',
                'contact_name' => 'Waldemar Grabowski',
                'contact_first_name' => 'Waldemar',
                'contact_last_name' => 'Grabowski',
                'contact_phone' => '501 654 274',
                'contact_email' => 'waldemar.grabowski@zdalna-lekcja.pl',
                'buyer_person_first_name' => 'Waldemar',
                'buyer_person_last_name' => 'Grabowski',
                'participant_first_name' => 'Waldemar',
                'participant_last_name' => 'Grabowski',
                'participant_email' => 'waldemar.grabowski@hostnet.pl',
                'invoice_notes' => 'Dane testowe - Waldek',
                'payment_terms' => 14,
            ];
        }
        
        // Pobierz dane zalogowanego użytkownika (jeśli jest zalogowany)
        $user = auth()->user();
        
        return view('courses.deferred-order', compact('course', 'testData', 'isTestMode', 'isEditMode', 'user'));
    }

    /**
     * Wyświetl nowy formularz zamówienia (kopia, do dalszych zmian).
     */
    public function orderForm($id, $ident = null)
    {
        $course = \App\Models\Course::with('priceVariants')->findOrFail($id);

        // Tryb testowy: ?test=1 włącza, ?test=0 wyłącza. Bez parametru – ustawienie z panelu (Zakupy pnedu.pl).
        $isTestMode = request()->has('test')
            ? (bool) request()->boolean('test')
            : (\App\Models\PaymentDisplayOption::getForCoursePage()['order_form_auto_fill_test_data'] ?? false);

        // Sprawdź czy to edycja istniejącego zamówienia (opcjonalnie, przez ident)
        $orderData = [];
        $isEditMode = false;

        if ($ident) {
            $existingOrder = FormOrder::where('ident', $ident)->first();
            if ($existingOrder && $existingOrder->product_id == $id) {
                $isEditMode = true;
                $orderData = [
                    'buyer_name' => $existingOrder->buyer_name,
                    'buyer_address' => $existingOrder->buyer_address,
                    'buyer_postcode' => $existingOrder->buyer_postal_code,
                    'buyer_city' => $existingOrder->buyer_city,
                    'buyer_nip' => $existingOrder->buyer_nip,
                    'buyer_nip8' => $existingOrder->buyer_nip,
                    'recipient_name' => $existingOrder->recipient_name,
                    'recipient_address' => $existingOrder->recipient_address,
                    'recipient_postcode' => $existingOrder->recipient_postal_code,
                    'recipient_city' => $existingOrder->recipient_city,
                    'recipient_nip' => $existingOrder->recipient_nip,
                    'recipient_nip8' => $existingOrder->recipient_nip,
                    'contact_name' => $existingOrder->orderer_name,
                    'contact_phone' => $existingOrder->orderer_phone,
                    'contact_email' => $existingOrder->orderer_email,
                    'participant_first_name' => explode(' ', $existingOrder->participant_name)[0] ?? '',
                    'participant_last_name' => implode(' ', array_slice(explode(' ', $existingOrder->participant_name), 1)),
                    'participant_email' => $existingOrder->participant_email,
                    'invoice_notes' => $existingOrder->invoice_notes,
                    'payment_terms' => $existingOrder->invoice_payment_delay ?? $existingOrder->ptw,
                    'order_id' => $existingOrder->id,
                    'order_ident' => $existingOrder->ident,
                ];
            }
        }

        $testData = $orderData;
        if (empty($testData) && $isTestMode) {
            $testData = [
                'buyer_name' => 'Gmina Bieżuń',
                'buyer_address' => 'ul. Warszawska 5',
                'buyer_postcode' => '09-320',
                'buyer_city' => 'Bieżuń',
                'buyer_nip' => '5110265245',
                'buyer_nip8' => '5110265245',
                'recipient_name' => 'Szkoła Podstawowa im. Andrzeja Zamoyskiego',
                'recipient_address' => 'ul. Andrzeja Zamoyskiego 28',
                'recipient_postcode' => '09-320',
                'recipient_city' => 'Bieżuń',
                'recipient_nip8' => '5261040828',
                'contact_name' => 'Waldemar Grabowski',
                'contact_first_name' => 'Waldemar',
                'contact_last_name' => 'Grabowski',
                'contact_phone' => '501 654 274',
                'contact_email' => 'waldemar.grabowski@zdalna-lekcja.pl',
                'buyer_person_first_name' => 'Waldemar',
                'buyer_person_last_name' => 'Grabowski',
                'participant_first_name' => 'Waldemar',
                'participant_last_name' => 'Grabowski',
                'participant_email' => 'waldemar.grabowski@hostnet.pl',
                'invoice_notes' => 'Dane testowe - Waldek',
                'payment_terms' => 14,
            ];
        }

        $user = auth()->user();

        return view('courses.order-form', compact('course', 'testData', 'isTestMode', 'isEditMode', 'user'));
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
        if (!$participant) {
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
     * Zapisz zamówienie z odroczonym terminem płatności.
     */
    public function storeDeferredOrder(Request $request, $id)
    {
        $course = Course::with('priceVariants')->findOrFail($id);

        // Walidacja danych
        $validated = $request->validate([
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
            'participant_first_name' => 'required|string|max:255',
            'participant_last_name' => 'required|string|max:255',
            'participant_email' => 'required|email|max:255',
            'invoice_notes' => 'nullable|string',
            'payment_terms' => 'required|integer|min:0|max:31',
        ], [
            'buyer_name.required' => 'Nazwa nabywcy jest wymagana.',
            'buyer_address.required' => 'Adres jest wymagany.',
            'buyer_postcode.required' => 'Kod pocztowy jest wymagany.',
            'buyer_city.required' => 'Miasto jest wymagane.',
            'buyer_nip.required' => 'NIP jest wymagany.',
            'contact_name.required' => 'Nazwa/imię nazwisko jest wymagane.',
            'contact_phone.required' => 'Telefon kontaktowy jest wymagany.',
            'contact_email.required' => 'E-mail jest wymagany.',
            'contact_email.email' => 'Podaj prawidłowy adres e-mail.',
            'participant_first_name.required' => 'Imię uczestnika jest wymagane.',
            'participant_last_name.required' => 'Nazwisko uczestnika jest wymagane.',
            'participant_email.required' => 'E-mail uczestnika jest wymagany.',
            'participant_email.email' => 'Podaj prawidłowy adres e-mail uczestnika.',
            'payment_terms.required' => 'Termin płatności jest wymagany.',
            'payment_terms.min' => 'Termin płatności musi być od 0 do 31 dni.',
            'payment_terms.max' => 'Termin płatności musi być od 0 do 31 dni.',
        ]);

        try {
            // Określ publigo_product_id - dla kursów z Publigo użyj id_old
            $publicoProductId = null;
            if ($course->source_id_old === 'certgen_Publigo' && $course->id_old) {
                $publicoProductId = $course->id_old;
            } elseif ($course->publigo_product_id) {
                $publicoProductId = $course->publigo_product_id;
            }

            // Pobierz aktualną cenę kursu (z uwzględnieniem promocji)
            $currentPrice = null;
            $priceInfo = $course->getCurrentPrice();
            if ($priceInfo) {
                $currentPrice = $priceInfo['price'];
            }

            // Sprawdź czy to edycja istniejącego zamówienia
            $order = null;
            if ($request->has('order_ident') && $request->order_ident) {
                $order = FormOrder::where('ident', $request->order_ident)
                    ->where('product_id', $id)
                    ->first();
            }

            // Dane do zapisania
            $orderData = [
                'ptw' => $validated['payment_terms'],
                'product_id' => $course->id,
                'product_name' => $course->title,
                'product_price' => $currentPrice,
                'product_description' => strip_tags($course->description ?? ''),
                'publigo_product_id' => $publicoProductId,
                'publigo_price_id' => $course->publigo_price_id,
                'participant_name' => $validated['participant_first_name'] . ' ' . $validated['participant_last_name'],
                'participant_email' => $validated['participant_email'],
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
                'ip_address' => $request->ip(),
            ];

            // Aktualizuj istniejące zamówienie lub utwórz nowe
            if ($order) {
                $order->update($orderData);
                Log::info('Deferred order updated', [
                    'order_id' => $order->id,
                    'ident' => $order->ident,
                    'course_id' => $course->id,
                    'participant_email' => $order->participant_email,
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
                    'participant_email' => $order->participant_email,
                ]);
            }

            // Zapisz uczestnika w form_order_participants (dla przyszłej obsługi wielu uczestników)
            FormOrderParticipant::syncFromFormOrder(
                $order,
                $validated['participant_first_name'],
                $validated['participant_last_name'],
                $validated['participant_email']
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
    public function storeOrderForm(Request $request, $id)
    {
        $course = Course::with('priceVariants')->findOrFail($id);

        $buyerType = $request->input('buyer_type', 'organisation');
        if (!in_array($buyerType, ['organisation', 'person'], true)) {
            $buyerType = 'organisation';
        }

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

            'participant_first_name' => 'required|string|max:255',
            'participant_last_name' => 'required|string|max:255',
            'participant_email' => 'required|email|max:255',

            'invoice_notes' => 'nullable|string',
            'payment_terms' => 'nullable|integer|min:0|max:31',
            'payment_gateway' => 'nullable|in:payu,paynow',
        ];

        if ($buyerType === 'organisation') {
            $rules['buyer_name'] = 'required|string|max:500';
        } else {
            // osoba fizyczna: bez nazwy nabywcy
            $rules['buyer_name'] = 'nullable|string|max:500';
            $rules['buyer_person_first_name'] = 'required|string|max:255';
            $rules['buyer_person_last_name'] = 'required|string|max:255';
        }

        $validated = $request->validate($rules, [
            'buyer_type.required' => 'Wybierz, jako kto zamawiasz.',
            'buyer_type.in' => 'Wybierz prawidłową opcję.',
            'payment_type.required' => 'Wybierz sposób rozliczenia.',
            'payment_type.in' => 'Wybierz prawidłowy sposób rozliczenia.',
        ]);

        // Dodatkowa walidacja: termin płatności wymagany tylko dla faktury z odroczonym terminem
        if (($validated['payment_type'] ?? null) === 'deferred' && (!isset($validated['payment_terms']) || $validated['payment_terms'] === '')) {
            return back()
                ->withErrors(['payment_terms' => 'Podaj termin płatności dla faktury z odroczonym terminem (0–31 dni).'])
                ->withInput();
        }

        if (($validated['payment_type'] ?? null) === 'online' && empty($validated['payment_gateway'])) {
            return back()
                ->withErrors(['payment_gateway' => 'Wybierz bramkę płatności.'])
                ->withInput();
        }

        // Płatność online – utwórz OnlinePaymentOrder i przekieruj do bramki
        if (($validated['payment_type'] ?? null) === 'online') {
            return $this->processOrderFormOnlinePayment($request, $course, $validated, $buyerType);
        }

        try {
            // Określ publigo_product_id - dla kursów z Publigo użyj id_old
            $publicoProductId = null;
            if ($course->source_id_old === 'certgen_Publigo' && $course->id_old) {
                $publicoProductId = $course->id_old;
            } elseif ($course->publigo_product_id) {
                $publicoProductId = $course->publigo_product_id;
            }

            // Pobierz aktualną cenę kursu (z uwzględnieniem promocji)
            $currentPrice = null;
            $priceInfo = $course->getCurrentPrice();
            if ($priceInfo) {
                $currentPrice = $priceInfo['price'];
            }

            // Sprawdź czy to edycja istniejącego zamówienia
            $order = null;
            if ($request->filled('order_ident')) {
                $order = FormOrder::where('ident', $request->order_ident)
                    ->where('product_id', $id)
                    ->first();
            }

            $buyerName = $validated['buyer_name'] ?? null;
            // na razie mapujemy NIP z pola buyer_nip8 (jeśli podany)
            $buyerNip = $request->input('buyer_nip8') ?: null;
            if ($buyerType === 'person') {
                $buyerName = trim(($validated['buyer_person_first_name'] ?? '') . ' ' . ($validated['buyer_person_last_name'] ?? '')) ?: ($validated['contact_name'] ?? $buyerName);
                $buyerNip = null;
            }

            $orderData = [
                'ptw' => $validated['payment_terms'],
                'product_id' => $course->id,
                'product_name' => $course->title,
                'product_price' => $currentPrice,
                'product_description' => strip_tags($course->description ?? ''),
                'publigo_product_id' => $publicoProductId,
                'publigo_price_id' => $course->publigo_price_id,
                'participant_name' => $validated['participant_first_name'] . ' ' . $validated['participant_last_name'],
                'participant_email' => $validated['participant_email'],
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
                'recipient_name' => $buyerType === 'organisation' ? ($validated['recipient_name'] ?? null) : null,
                'recipient_address' => $buyerType === 'organisation' ? ($validated['recipient_address'] ?? null) : null,
                'recipient_postal_code' => $buyerType === 'organisation' ? ($validated['recipient_postcode'] ?? null) : null,
                'recipient_city' => $buyerType === 'organisation' ? ($validated['recipient_city'] ?? null) : null,
                'recipient_nip' => $buyerType === 'organisation' ? ($request->input('recipient_nip8') ?: null) : null,
                'invoice_notes' => $validated['invoice_notes'],
                'invoice_payment_delay' => $validated['payment_terms'] ?? null,
                'ip_address' => $request->ip(),
            ];

            if ($order) {
                $order->update($orderData);
            } else {
                $orderData['ident'] = FormOrder::generateIdent();
                $orderData['order_date'] = now('UTC');
                $orderData['publigo_sent'] = 0;
                $orderData['status_completed'] = 0;
                $order = FormOrder::create($orderData);
            }

            // Zapisz uczestnika w form_order_participants (dla przyszłej obsługi wielu uczestników)
            FormOrderParticipant::syncFromFormOrder(
                $order,
                $validated['participant_first_name'],
                $validated['participant_last_name'],
                $validated['participant_email']
            );

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
     * Przetwórz płatność online z formularza order-form – utwórz OnlinePaymentOrder i przekieruj do bramki.
     */
    protected function processOrderFormOnlinePayment(Request $request, Course $course, array $validated, string $buyerType)
    {
        $priceInfo = $course->getCurrentPrice();
        $totalAmount = $priceInfo['price'] ?? 0;

        if ($totalAmount <= 0) {
            return redirect()->route('payment.order-form', $course->id)
                ->with('error', 'To szkolenie nie ma ustawionej ceny. Skontaktuj się z organizatorem lub wybierz formularz zamówienia z odroczonym terminem płatności.')
                ->withInput();
        }

        // Uczestnik szkolenia – dane do rejestracji po płatności
        $firstName = $validated['participant_first_name'];
        $lastName = $validated['participant_last_name'];
        $email = $validated['participant_email'];
        $phone = $validated['contact_phone'];

        // address_data – dane do faktury (zgodne ze strukturą pay-online)
        $addressData = $this->collectOrderFormAddressData($request, $buyerType);

        $formData = $request->except(['_token']);
        $paymentGateway = $validated['payment_gateway'] ?? 'payu';

        $order = \App\Models\OnlinePaymentOrder::create([
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

        if ($paymentGateway === 'payu') {
            $payuService = new \App\Services\PayUService();
            $notifyUrl = route('payment.payu.notify');
            $continueUrl = route('payment.payu.return');
            $result = $payuService->createOrder($order, $notifyUrl, $continueUrl);

            if (!$result['success']) {
                $errorMsg = $result['error'] ?? 'Nie udało się połączyć z PayU. Spróbuj ponownie.';
                return redirect()->route('payment.order-form', $course->id)
                    ->with('error', $errorMsg)
                    ->withInput();
            }
            session(['payu_order_ident' => $order->ident]);
            session(['payu_order_email' => $order->email]);
            return redirect()->away($result['redirect_uri']);
        }

        if ($paymentGateway === 'paynow') {
            $paynowService = new \App\Services\PayNowService();
            $notifyUrl = route('payment.paynow.notify');
            $continueUrl = route('payment.paynow.return');
            $result = $paynowService->createOrder($order, $notifyUrl, $continueUrl);

            if (!$result['success']) {
                $errorMsg = $result['error'] ?? 'Nie udało się połączyć z PayNow. Spróbuj ponownie.';
                return redirect()->route('payment.order-form', $course->id)
                    ->with('error', $errorMsg)
                    ->withInput();
            }
            return redirect()->away($result['redirect_url']);
        }

        return redirect()->route('payment.order-form', $course->id)
            ->with('error', 'Nieznana bramka płatności.')
            ->withInput();
    }

    /**
     * Zbierz dane adresowe z formularza order-form.
     */
    protected function collectOrderFormAddressData(Request $request, string $buyerType): array
    {
        if ($buyerType === 'organisation') {
            return [
                'buyer' => [
                    'nip' => $request->input('buyer_nip8'),
                    'country' => 'Polska',
                    'name' => $request->input('buyer_name'),
                    'street' => $request->input('buyer_address'),
                    'building_no' => '',
                    'flat_no' => '',
                    'postcode' => $request->input('buyer_postcode'),
                    'city' => $request->input('buyer_city'),
                ],
                'recipient' => [
                    'nip' => $request->input('recipient_nip8'),
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
        $buyerName = trim(($request->input('buyer_person_first_name') ?? '') . ' ' . ($request->input('buyer_person_last_name') ?? ''));
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
        $order = FormOrder::where('ident', $ident)->firstOrFail();
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

                // 2. Uczestnik – jeśli inny niż zamawiający
                $participantEmail = $order->participant_email;
                if ($participantEmail) {
                    $normalizedParticipant = strtolower(trim($participantEmail));
                    if (!in_array($normalizedParticipant, $emailsToSend)) {
                        $emailsToSend[] = $normalizedParticipant;
                    }
                }

                // 3. Kopia dla admina
                $adminEmail = 'waldemar.grabowski@hostnet.pl';
                if (!in_array(strtolower($adminEmail), $emailsToSend)) {
                    $emailsToSend[] = $adminEmail;
                }
            
            Log::info('Próba wysyłki e-maila z zamówieniem', [
                'order_id' => $order->id,
                'order_ident' => $order->ident,
                'emails' => $emailsToSend
            ]);
            
            // Wyślij e-mail na wszystkie adresy
            foreach ($emailsToSend as $email) {
                try {
                    Mail::to($email)
                        ->send(new OrderNotificationMail($order, $course));
                    
                    Log::info('E-mail z zamówieniem został wysłany', [
                        'order_id' => $order->id,
                        'order_ident' => $order->ident,
                        'email' => $email
                    ]);
                } catch (Exception $emailException) {
                    // Loguj błąd dla konkretnego adresu, ale kontynuuj wysyłkę na pozostałe
                    Log::error('Błąd wysyłki e-maila z zamówieniem na konkretny adres: ' . $emailException->getMessage(), [
                        'order_id' => $order->id,
                        'order_ident' => $order->ident,
                        'email' => $email,
                        'exception' => $emailException->getTraceAsString()
                    ]);
                }
            }
            }
            
        } catch (Exception $e) {
            // Loguj błąd, ale nie blokuj wyświetlania podsumowania
            Log::error('Błąd wysyłki e-maila z zamówieniem: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'order_ident' => $order->ident,
                'exception' => $e->getTraceAsString()
            ]);
        }

        return view('orders.summary', compact('order', 'course'));
    }

    /**
     * Generuj PDF z zamówieniem.
     */
    public function orderPdf($ident)
    {
        $order = FormOrder::where('ident', $ident)->firstOrFail();
        $course = $order->course;

        $pdf = Pdf::loadView('orders.pdf', compact('order', 'course'));
        
        return $pdf->stream('zamowienie-' . $order->ident . '.pdf');
    }
}