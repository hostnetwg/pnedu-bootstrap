<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\FormOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\OrderNotificationMail;

class CourseController extends Controller
{
    /**
     * Display a listing of online live courses.
     *
     * @return \Illuminate\View\View
     */
    public function onlineLive(Request $request)
    {
        try {
            $sort = $request->query('sort', 'desc');
            $sort = in_array($sort, ['asc', 'desc']) ? $sort : 'desc';
            $instructorId = $request->query('instructor');
            $dateFilter = $request->query('date_filter', 'all');
            $paidFilter = $request->query('paid_filter');
            $typeFilter = $request->query('type_filter');
            $categoryFilter = $request->query('category_filter');
            $searchQuery = $request->query('q');

            // Get instructors who have online courses
            $instructors = \App\Models\Instructor::whereHas('courses', function($q) {
                $q->where('type', 'online')->where('is_active', true);
            })->orderBy('last_name')->get();

            $coursesQuery = Course::with('instructor')
                ->where('is_active', true);

            if ($typeFilter === 'online' || $typeFilter === 'offline') {
                $coursesQuery->where('type', $typeFilter);
            } else {
                $coursesQuery->where('type', 'online'); // default
            }

            if ($instructorId) {
                $coursesQuery->where('instructor_id', $instructorId);
            }

            if ($dateFilter === 'upcoming') {
                $coursesQuery->where('start_date', '>', now());
            } elseif ($dateFilter === 'archived') {
                $coursesQuery->whereNotNull('end_date')->where('end_date', '<', now());
            } elseif ($dateFilter === 'ongoing') {
                $coursesQuery->where('start_date', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                    });
            }

            if ($paidFilter === 'paid') {
                $coursesQuery->where('is_paid', 1);
            } elseif ($paidFilter === 'free') {
                $coursesQuery->where('is_paid', 0);
            }

            if ($categoryFilter === 'otwarte') {
                $coursesQuery->where('category', 'open');
            } elseif ($categoryFilter === 'zamknięte') {
                $coursesQuery->where('category', 'closed');
            }

            if (!empty($searchQuery)) {
                $coursesQuery->where(function($q) use ($searchQuery) {
                    $q->where('title', 'like', '%' . $searchQuery . '%')
                      ->orWhere('description', 'like', '%' . $searchQuery . '%');
                });
            }

            $courses = $coursesQuery
                ->orderBy('start_date', $sort)
                ->paginate(20)
                ->appends([
                    'sort' => $sort,
                    'instructor' => $instructorId,
                    'date_filter' => $dateFilter,
                    'paid_filter' => $paidFilter,
                    'type_filter' => $typeFilter,
                    'category_filter' => $categoryFilter,
                    'q' => $searchQuery
                ]);

            return view('courses.online-live', compact('courses', 'sort', 'instructors', 'instructorId', 'dateFilter', 'paidFilter', 'typeFilter', 'categoryFilter', 'searchQuery'));
        } catch (Exception $e) {
            // Log the error for administrators
            Log::error('Error accessing courses: ' . $e->getMessage());
            
            // Return the view with an empty collection and error flag
            return view('courses.online-live', [
                'courses' => collect([]),
                'databaseError' => true
            ]);
        }
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
        
        return view('courses.show', compact('course'));
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
                'recipient_name' => 'Szkoła Podstawowa im. Andrzeja Zamoyskiego',
                'recipient_address' => 'ul. Andrzeja Zamoyskiego 28',
                'recipient_postcode' => '09-320',
                'recipient_city' => 'Bieżuń',
                'contact_name' => 'Waldemar Grabowski',
                'contact_phone' => '501 654 274',
                'contact_email' => 'waldemar.grabowski@zdalna-lekcja.pl',
                'participant_first_name' => 'Waldemar',
                'participant_last_name' => 'Grabowski',
                'participant_email' => 'waldemar.grabowski@hostnet.pl',
                'participant_birth_date' => '1970-01-01',
                'participant_birth_place' => 'Warszawa',
                'invoice_notes' => 'Dane testowe - Waldek',
                'payment_terms' => 14,
            ];
        }
        
        // Pobierz dane zalogowanego użytkownika (jeśli jest zalogowany)
        $user = auth()->user();
        
        return view('courses.deferred-order', compact('course', 'testData', 'isTestMode', 'isEditMode', 'user'));
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
            'payment_terms' => 'required|integer|min:1',
            'consent' => 'required|accepted',
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
            'payment_terms.min' => 'Termin płatności musi być większy niż 0 dni.',
            'consent.required' => 'Musisz wyrazić zgodę na przetwarzanie danych osobowych.',
            'consent.accepted' => 'Musisz wyrazić zgodę na przetwarzanie danych osobowych.',
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
                'invoice_payment_delay' => $validated['payment_terms'],
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
                $orderData['order_date'] = now();
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

            // Przekierowanie do strony podsumowania z PDF
            return redirect()
                ->route('orders.summary', ['ident' => $order->ident])
                ->with('success', 'Zamówienie zostało złożone pomyślnie!');

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
     * Wyświetl podsumowanie zamówienia z PDF.
     */
    public function orderSummary($ident)
    {
        $order = FormOrder::where('ident', $ident)->firstOrFail();
        $course = $order->course;

        // Wyślij e-mail z załączonym PDF
        try {
            // Przygotuj listę adresów do wysłania
            $emailsToSend = [];
            
            // Adres uczestnika
            $participantEmail = $order->participant_email;
            if ($participantEmail) {
                $emailsToSend[] = strtolower(trim($participantEmail));
            }
            
            // Adres do faktury (orderer_email)
            $ordererEmail = $order->orderer_email;
            if ($ordererEmail) {
                $normalizedOrdererEmail = strtolower(trim($ordererEmail));
                // Dodaj tylko jeśli różni się od adresu uczestnika
                if (!in_array($normalizedOrdererEmail, $emailsToSend)) {
                    $emailsToSend[] = $normalizedOrdererEmail;
                }
            }
            
            // Zawsze dodaj adres waldemar.grabowski@hostnet.pl
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