<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
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

// Szkolenia online LIVE
Route::get('/szkolenia-online-live', [App\Http\Controllers\CourseController::class, 'onlineLive'])
    ->name('courses.online-live');

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


Route::get('/dashboard', function () {
    return view('dashboard.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard/szkolenia', function () {
    return view('dashboard.szkolenia');
})->middleware(['auth', 'verified'])->name('dashboard.szkolenia');

Route::get('/dashboard/zaswiadczenia', function () {
    return view('dashboard.zaswiadczenia');
})->middleware(['auth', 'verified'])->name('dashboard.zaswiadczenia');

Route::get('/dashboard/moje-dane', function () {
    return view('dashboard.moje-dane');
})->middleware(['auth', 'verified'])->name('dashboard.moje-dane');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Handle contact form submissions from the welcome page
Route::post('/kontakt', [ContactController::class, 'send'])->name('contact.send');

// Szczegóły szkolenia
Route::get('/courses/{id}', [App\Http\Controllers\CourseController::class, 'show'])->name('courses.show');
// Płatność online
Route::get('/courses/{id}/pay-online', [App\Http\Controllers\CourseController::class, 'payOnline'])->name('payment.online');
// Zamówienie z odroczonym terminem
Route::get('/courses/{id}/deferred-order/test', [App\Http\Controllers\CourseController::class, 'deferredOrder'])->name('payment.deferred.test');
Route::get('/courses/{id}/deferred-order/edit/{ident}', [App\Http\Controllers\CourseController::class, 'deferredOrder'])->name('payment.deferred.edit');
Route::get('/courses/{id}/deferred-order', [App\Http\Controllers\CourseController::class, 'deferredOrder'])->name('payment.deferred');
Route::post('/courses/{id}/deferred-order', [App\Http\Controllers\CourseController::class, 'storeDeferredOrder'])->name('payment.deferred.store');

// Podsumowanie i PDF zamówienia
Route::get('/orders/{ident}/summary', [App\Http\Controllers\CourseController::class, 'orderSummary'])->name('orders.summary');
Route::get('/orders/{ident}/pdf', [App\Http\Controllers\CourseController::class, 'orderPdf'])->name('orders.pdf');
