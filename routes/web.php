<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/rodo', function () {
    return view('rodo');
})->name('rodo');

Route::get('/regulamin', function () {
    return view('regulamin');
})->name('regulamin');

Route::get('/polityka-prywatnosci', function () {
    return view('polityka-prywatnosci');
})->name('polityka-prywatnosci');

// Blog: lista artykułów
Route::get('/blog', function () {
    return view('blog.index');
})->name('blog.index');

// O nas - Zespół
Route::get('/o-nas/zespol', [App\Http\Controllers\AboutController::class, 'team'])->name('about.team');

// Szkolenia online LIVE
Route::get('/szkolenia-online-live', [App\Http\Controllers\CourseController::class, 'onlineLive'])
    ->name('courses.online-live');

// Szkolenia indywidualne
Route::get('/szkolenia-indywidualne', [App\Http\Controllers\CourseController::class, 'individualCourses'])
    ->name('courses.individual');

// Bezpłatne szkolenia (TIK w pracy NAUCZYCIELA)
Route::get('/bezplatne/tik-w-pracy-nauczyciela', [App\Http\Controllers\CourseController::class, 'freeCourses'])
    ->name('courses.free');

// Bezpłatne szkolenia (Szkolny ADMINISTRATOR Office 365)
Route::get('/bezplatne/szkolny-administrator-office-365', [App\Http\Controllers\CourseController::class, 'office365Courses'])
    ->name('courses.office365');

// Bezpłatne szkolenia (Akademia Rodzica)
Route::get('/bezplatne/akademia-rodzica', [App\Http\Controllers\CourseController::class, 'parentAcademyCourses'])
    ->name('courses.parent-academy');

// Bezpłatne szkolenia (Akademia Dyrektora)
Route::get('/bezplatne/akademia-dyrektora', [App\Http\Controllers\CourseController::class, 'directorAcademyCourses'])
    ->name('courses.director-academy');

// Artykuły bloga
Route::get('/blog/sztuczna-inteligencja-w-edukacji', function () {
    return view('blog.sztuczna-inteligencja-w-edukacji');
})->name('blog.sztuczna-inteligencja-w-edukacji');

Route::get('/blog/wykorzystanie-aplikacji-canva', function () {
    return view('blog.wykorzystanie-aplikacji-canva');
})->name('blog.wykorzystanie-aplikacji-canva');

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/szkolenia', [App\Http\Controllers\DashboardController::class, 'szkolenia'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.szkolenia');

Route::get('/dashboard/szkolenia/{participant}/wideo', [App\Http\Controllers\DashboardController::class, 'szkoleniaWideo'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.szkolenia.wideo');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/zaswiadczenia', [App\Http\Controllers\CertificateController::class, 'dashboardCertificatesIndex'])
        ->name('dashboard.zaswiadczenia');
    Route::get('/dashboard/zaswiadczenia/{course}/download-with-redirect', [App\Http\Controllers\CertificateController::class, 'dashboardCertificateDownloadWithRedirectPage'])
        ->whereNumber('course')
        ->name('dashboard.zaswiadczenia.course.download-redirect');
    Route::get('/dashboard/zaswiadczenia/{course}/download', [App\Http\Controllers\CertificateController::class, 'dashboardCertificateDownload'])
        ->whereNumber('course')
        ->name('dashboard.zaswiadczenia.course.download');
    Route::post('/dashboard/zaswiadczenia/{course}', [App\Http\Controllers\CertificateController::class, 'dashboardSubmitBirth'])
        ->whereNumber('course')
        ->name('dashboard.zaswiadczenia.course.birth');
    Route::get('/dashboard/zaswiadczenia/{course}', [App\Http\Controllers\CertificateController::class, 'dashboardCertificateShow'])
        ->whereNumber('course')
        ->name('dashboard.zaswiadczenia.course');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Handle contact form submissions from the welcome page
Route::post('/kontakt', [ContactController::class, 'send'])->name('contact.send');

// Lookup uczestnika po e-mailu (musi być przed /courses/{id})
Route::get('/courses/participant-lookup-by-email', [App\Http\Controllers\CourseController::class, 'participantLookupByEmail'])->name('courses.participant-lookup');
// Szczegóły szkolenia
Route::get('/courses/{id}', [App\Http\Controllers\CourseController::class, 'show'])->name('courses.show');
Route::post('/courses/{id}/register', [App\Http\Controllers\CourseController::class, 'register'])->name('courses.register');
// Płatność online
Route::get('/courses/{id}/pay-online', [App\Http\Controllers\CourseController::class, 'payOnline'])->name('payment.online');
Route::post('/courses/{id}/pay-online', [App\Http\Controllers\CourseController::class, 'storePayOnline'])->name('payment.online.store');

// PayU – webhook i return (CSRF exclude dla notify w bootstrap/app.php)
Route::post('/payment/payu/notify', [App\Http\Controllers\PaymentController::class, 'payuNotify'])->name('payment.payu.notify');
Route::get('/payment/payu/return', [App\Http\Controllers\PaymentController::class, 'payuReturn'])->name('payment.payu.return');

// PayNow – webhook i return (CSRF exclude dla notify w bootstrap/app.php)
Route::post('/payment/paynow/notify', [App\Http\Controllers\PaymentController::class, 'paynowNotify'])->name('payment.paynow.notify');
Route::get('/payment/paynow/return', [App\Http\Controllers\PaymentController::class, 'paynowReturn'])->name('payment.paynow.return');

// Strony po płatności
Route::get('/payment/success/{ident}', [App\Http\Controllers\PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/pending/{ident}', [App\Http\Controllers\PaymentController::class, 'pending'])->name('payment.pending');
// Zamówienie z odroczonym terminem
Route::get('/courses/{id}/deferred-order/test', [App\Http\Controllers\CourseController::class, 'deferredOrder'])->name('payment.deferred.test');
Route::get('/courses/{id}/deferred-order/edit/{ident}', [App\Http\Controllers\CourseController::class, 'deferredOrder'])->name('payment.deferred.edit');
Route::get('/courses/{id}/deferred-order', [App\Http\Controllers\CourseController::class, 'deferredOrder'])->name('payment.deferred');
Route::post('/courses/{id}/deferred-order', [App\Http\Controllers\CourseController::class, 'storeDeferredOrder'])->name('payment.deferred.store');

// Formularz zamówienia (główny)
Route::get('/courses/{id}/order-form/edit/{ident}', [App\Http\Controllers\CourseController::class, 'orderForm'])->name('payment.order-form.edit');
Route::get('/courses/{id}/order-form', [App\Http\Controllers\CourseController::class, 'orderForm'])->name('payment.order-form');
Route::post('/courses/{id}/order-form', [App\Http\Controllers\CourseController::class, 'storeOrderForm'])->name('payment.order-form.store');

// Podsumowanie i PDF zamówienia
Route::get('/orders/{ident}/summary', [App\Http\Controllers\CourseController::class, 'orderSummary'])->name('orders.summary');
Route::get('/orders/{ident}/pdf', [App\Http\Controllers\CourseController::class, 'orderPdf'])->name('orders.pdf');

// Rejestracja zaświadczenia (formularz po tokenie – zapis do participants w pneadm)
Route::get('/certificate-registration/{token}', [App\Http\Controllers\CertificateRegistrationController::class, 'show'])->name('certificate-registration.show');
Route::post('/certificate-registration/{token}', [App\Http\Controllers\CertificateRegistrationController::class, 'submit'])->name('certificate-registration.submit');

// Link z tokenem – lista szkoleń i pobieranie zaświadczeń (bez logowania)
Route::get('/certificates/{token}', [App\Http\Controllers\CertificateController::class, 'showListByToken'])->name('certificates.list-by-token');
Route::get('/certificate/{token}/{course}', [App\Http\Controllers\CertificateController::class, 'showCertificateByToken'])->name('certificates.show-by-token');
Route::post('/certificate/{token}/{course}', [App\Http\Controllers\CertificateController::class, 'submitBirthDataByToken'])->name('certificates.submit-birth-data');
Route::get('/certificate/{token}/{course}/download-with-redirect', [App\Http\Controllers\CertificateController::class, 'downloadWithRedirectPage'])->name('certificates.download-with-redirect');
Route::get('/certificate/{token}/{course}/download', [App\Http\Controllers\CertificateController::class, 'downloadByToken'])->name('certificates.download-by-token');

// Generowanie zaświadczeń (tylko dla zalogowanych użytkowników)
Route::middleware(['auth', 'verified'])->group(function () {
    // Route z course_id (dla kompatybilności wstecznej)
    Route::get('/courses/{course}/certificate', [App\Http\Controllers\CertificateController::class, 'generate'])->name('certificates.generate');
    // Route z participant_id (bardziej precyzyjne, jak w pneadm-bootstrap)
    Route::get('/certificates/generate/{participant}', [App\Http\Controllers\CertificateController::class, 'generateByParticipant'])->name('certificates.generate.by-participant');
});
