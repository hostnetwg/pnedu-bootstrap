<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\FormOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

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
     * Wyświetl szczegóły szkolenia.
     */
    public function show($id)
    {
        $course = \App\Models\Course::findOrFail($id);
        
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
    public function deferredOrder($id)
    {
        $course = \App\Models\Course::findOrFail($id);
        return view('courses.deferred-order', compact('course'));
    }

    /**
     * Zapisz zamówienie z odroczonym terminem płatności.
     */
    public function storeDeferredOrder(Request $request, $id)
    {
        $course = Course::findOrFail($id);

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

            // Utwórz zamówienie
            $order = FormOrder::create([
                'ident' => FormOrder::generateIdent(),
                'ptw' => $validated['payment_terms'],
                'order_date' => now(),
                'product_id' => $course->id,
                'product_name' => $course->title,
                'product_price' => null, // Można dodać pole price w courses jeśli istnieje
                'product_description' => strip_tags($course->description ?? ''),
                'publigo_product_id' => $publicoProductId,
                'publigo_price_id' => $course->publigo_price_id,
                'publigo_sent' => 0,
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
                'status_completed' => 0,
                'ip_address' => $request->ip(),
            ]);

            Log::info('Deferred order created', [
                'order_id' => $order->id,
                'ident' => $order->ident,
                'course_id' => $course->id,
                'participant_email' => $order->participant_email,
            ]);

            return redirect()
                ->route('courses.show', $course->id)
                ->with('success', 'Zamówienie zostało złożone pomyślnie! Numer zamówienia: ' . $order->ident);

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
}