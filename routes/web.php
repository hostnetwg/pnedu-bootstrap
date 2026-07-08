<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\ExternalSurveyGateController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SesNotificationWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');

Route::get('/media/pneadm/{path}', [App\Http\Controllers\PneadmMediaController::class, 'show'])
    ->where('path', '.*')
    ->name('pneadm.media');

Route::post('/api/internal/cache/upcoming-courses', [App\Http\Controllers\Internal\PneduCacheInvalidationController::class, 'forgetUpcomingCourses'])
    ->middleware('internal.api')
    ->name('internal.cache.upcoming-courses');

Route::get('/l/{campaign_code}', App\Http\Controllers\MarketingCampaignShortLinkController::class)
    ->where('campaign_code', '[A-Za-z0-9._-]+')
    ->middleware('throttle:180,1')
    ->name('marketing.short-link');

Route::get('/', [HomeController::class, 'index'])->name('home');

// Bramka ankiet (link dla uczestników bez ujawniania adresu panelu administratora).
Route::get('/ankieta/{token}', [ExternalSurveyGateController::class, 'visit'])
    ->middleware('throttle:120,1')
    ->where('token', '[a-z0-9]+')
    ->name('survey.gate.visit');

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

// O nas - Akredytacja MKO
Route::get('/o-nas/akredytacja-mko', [App\Http\Controllers\AboutController::class, 'accreditation'])
    ->name('about.accreditation');

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

Route::get('/dashboard/fragments/aktualna-oferta', [App\Http\Controllers\DashboardFragmentController::class, 'aktualnaOferta'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.fragments.aktualna-oferta');

Route::get('/dashboard/fragments/szkolenia-list', [App\Http\Controllers\DashboardFragmentController::class, 'szkoleniaList'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.fragments.szkolenia-list');

Route::get('/dashboard/szkolenia/{participant}/wideo', [App\Http\Controllers\DashboardController::class, 'szkoleniaWideo'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.szkolenia.wideo');

Route::post('/dashboard/szkolenia/{participant}/wideo/{video}/notatka', [App\Http\Controllers\DashboardController::class, 'saveTrainingVideoNote'])
    ->middleware(['auth', 'verified', 'throttle:60,1'])
    ->whereNumber('video')
    ->name('dashboard.szkolenia.wideo-note.save');

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

    Route::get('/dashboard/kursy-online', [App\Http\Controllers\DashboardOnlineCoursesController::class, 'index'])
        ->name('dashboard.online-courses.index');
    Route::get('/dashboard/kursy-online/{enrollment}', [App\Http\Controllers\DashboardOnlineCoursesController::class, 'show'])
        ->name('dashboard.online-courses.show');
    Route::get('/dashboard/kursy-online/{enrollment}/lekcje/{lesson}', [App\Http\Controllers\DashboardOnlineCoursesController::class, 'lesson'])
        ->whereNumber('lesson')
        ->name('dashboard.online-courses.lesson');
    Route::post('/dashboard/kursy-online/{enrollment}/lekcje/{lesson}/ukonczenie', [App\Http\Controllers\DashboardOnlineCoursesController::class, 'toggleLessonCompletion'])
        ->whereNumber('lesson')
        ->middleware('throttle:120,1')
        ->name('dashboard.online-courses.lesson-completion.toggle');
    Route::post('/dashboard/kursy-online/{enrollment}/lekcje/{lesson}/notatka', [App\Http\Controllers\DashboardOnlineCoursesController::class, 'saveLessonNote'])
        ->whereNumber('lesson')
        ->middleware('throttle:60,1')
        ->name('dashboard.online-courses.lesson-note.save');
    Route::post('/dashboard/kursy-online/{enrollment}/lekcje/{lesson}/zaswiadczenie', [App\Http\Controllers\OnlineCourseLessonCertificateController::class, 'submit'])
        ->whereNumber('lesson')
        ->middleware('throttle:30,1')
        ->name('dashboard.online-courses.lesson-certificate.submit');

    Route::get('/dashboard/kursy-online/{enrollment}/zaswiadczenie', [App\Http\Controllers\OnlineCourseCertificateController::class, 'show'])
        ->name('dashboard.online-courses.certificate.show');
    Route::post('/dashboard/kursy-online/{enrollment}/zaswiadczenie/profil', [App\Http\Controllers\OnlineCourseCertificateController::class, 'updateProfile'])
        ->name('dashboard.online-courses.certificate.profile');
    Route::get('/dashboard/kursy-online/{enrollment}/zaswiadczenie/pobierz', [App\Http\Controllers\OnlineCourseCertificateController::class, 'download'])
        ->name('dashboard.online-courses.certificate.download');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Handle contact form submissions from the welcome page
Route::post('/kontakt', [ContactController::class, 'send'])->name('contact.send');

Route::post('/newsletter', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:10,1')
    ->name('newsletter.subscribe');

Route::post('/webhooks/ses/notifications', SesNotificationWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.ses.notifications');

// Lookup uczestnika po e-mailu (musi być przed /courses/{id})
Route::get('/courses/participant-lookup-by-email', [App\Http\Controllers\CourseController::class, 'participantLookupByEmail'])->name('courses.participant-lookup');
// Szczegóły szkolenia
Route::get('/courses/{id}', [App\Http\Controllers\CourseController::class, 'show'])
    ->middleware(\App\Http\Middleware\TrackCoursePageView::class.':course_show')
    ->name('courses.show');
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
Route::get('/courses/{id}/deferred-order/edit/{ident}', [App\Http\Controllers\CourseController::class, 'deferredOrder'])
    ->middleware(\App\Http\Middleware\TrackCoursePageView::class.':order_form')
    ->name('payment.deferred.edit');
Route::get('/courses/{id}/deferred-order', [App\Http\Controllers\CourseController::class, 'deferredOrder'])
    ->middleware(\App\Http\Middleware\TrackCoursePageView::class.':order_form')
    ->name('payment.deferred');
Route::post('/courses/{id}/deferred-order', [App\Http\Controllers\CourseController::class, 'storeDeferredOrder'])->name('payment.deferred.store');

// Formularz zamówienia (główny)
Route::get('/courses/{id}/order-form/edit/{ident}', [App\Http\Controllers\CourseController::class, 'orderForm'])
    ->middleware(\App\Http\Middleware\TrackCoursePageView::class.':order_form')
    ->name('payment.order-form.edit');
Route::get('/courses/{id}/order-form', [App\Http\Controllers\CourseController::class, 'orderForm'])
    ->middleware(\App\Http\Middleware\TrackCoursePageView::class.':order_form')
    ->name('payment.order-form');
Route::post('/courses/{id}/order-form', [App\Http\Controllers\CourseController::class, 'storeOrderForm'])->name('payment.order-form.store');

// Etap B1 — publiczny endpoint JS analityki (batch eventów formularza). Fail-silent, zawsze 204.
Route::post('/analytics/client-events', [App\Http\Controllers\Analytics\ClientEventController::class, 'store'])
    ->middleware('throttle:analytics-client-events')
    ->name('analytics.client-events.store');

// Podsumowanie i PDF zamówienia
Route::get('/orders/{ident}/summary', [App\Http\Controllers\CourseController::class, 'orderSummary'])->name('orders.summary');
Route::get('/orders/{ident}/pdf', [App\Http\Controllers\CourseController::class, 'orderPdf'])->name('orders.pdf');

// Rejestracja zaświadczenia (formularz po tokenie – zapis do participants w pneadm)
Route::middleware([
    \App\Http\Middleware\CacheCertificateRegistrationPage::class,
])->withoutMiddleware([
    \App\Http\Middleware\CaptureMarketingSource::class,
])->group(function () {
    Route::get('/certificate-registration/{token}', [App\Http\Controllers\CertificateRegistrationController::class, 'show'])->name('certificate-registration.show');
    Route::post('/certificate-registration/{token}', [App\Http\Controllers\CertificateRegistrationController::class, 'submit'])->name('certificate-registration.submit');
});

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
