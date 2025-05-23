{{-- resources/views/welcome.blade.php --}}
@extends('layouts.app')

@section('title', 'Platforma Nowoczesnej Edukacji – Witamy')

@section('content')

@section('main-padding', '')

<!-- ===== HERO BANNER ======================================= -->
<div class="bg-primary bg-gradient text-white py-3 text-center">
    <div class="container">
        <p class="lead fw-semibold mb-0">
            Niepubliczny Ośrodek Doskonalenia Nauczycieli "Platforma Nowoczesnej Edukacji"<br>
            <span style="color: #c6a300; font-style: normal; font-weight: 600;">
                AKREDYTACJA MAZOWIECKIEGO KURATORA OŚWIATY
            </span>
        </p>
    </div>
</div>

<!-- ===== IMPROVED CAROUSEL ======================================= -->
<div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slajd 1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slajd 2"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slajd 3"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="3" aria-label="Slajd 4"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="4" aria-label="Slajd 5"></button>
    </div>
    <div class="carousel-inner">
        <div class="carousel-item active" style="height: 400px; background: linear-gradient(135deg, #0a1126 0%, #1a2a56 100%);">
            <div class="container h-100">
                <div class="row h-100 align-items-center">
                    <div class="col-md-6 text-white">
                        <h1 class="display-4 fw-bold mb-4">Szkolenia dla nauczycieli</h1>
                        <p class="lead mb-4">Nasze szkolenia są tworzone przez praktyków edukacji i skoncentrowane na aktualnych wyzwaniach dydaktycznych. Poznasz narzędzia i metody, które od razu możesz zastosować w swojej klasie.</p>
                        <a href="#courses" class="btn btn-light btn-lg">Sprawdź ofertę</a>
                    </div>
                    <div class="col-md-6 d-none d-md-block">
                        <img src="{{ asset('images/carousel/szkolenia_dla_nauczycieli_02.png') }}"
                                class="d-block w-100"
                                alt="Szkolenia dla nauczycieli"
                                style="height: 320px; object-fit: cover;">
                    </div>
                </div>
            </div>
        </div>
        <div class="carousel-item" style="height: 400px; background: linear-gradient(135deg, #14213d 0%, #2a3f5f 100%);">
            <div class="container h-100">
                <div class="row h-100 align-items-center">
                    <div class="col-md-6 text-white">
                        <h1 class="display-4 fw-bold mb-4">Szkolenia dla dyrektorów</h1>
                        <p class="lead mb-4">Wspieramy liderów oświaty w skutecznym zarządzaniu szkołą. Od przepisów prawa po budowanie kultury współpracy i skutecznego nadzoru pedagogicznego.</p>
                        <a href="#courses" class="btn btn-light btn-lg">Dowiedz się więcej</a>
                    </div>
                    <div class="col-md-6 d-none d-md-block">
                        <img src="images/carousel/szkolenia_dla_dyrektorow_01.png" class="img-fluid rounded shadow-lg" alt="Szkolenia dla dyrektorów">
                    </div>
                </div>
            </div>
        </div>
        <div class="carousel-item" style="height: 400px; background: linear-gradient(135deg, #1b1f3b 0%, #2d3558 100%);">
            <div class="container h-100">
                <div class="row h-100 align-items-center">
                    <div class="col-md-6 text-white">
                        <h1 class="display-4 fw-bold mb-4">Szkolenia dla rad pedagogicznych</h1>
                        <p class="lead mb-4">Zapewniamy kompleksowe wsparcie w organizacji rad pedagogicznych, które naprawdę angażują i rozwijają kompetencje zespołu nauczycieli.</p>
                        <a href="https://nowoczesna-edukacja.pl" class="btn btn-light btn-lg">Zobacz ofertę</a>
                    </div>
                    <div class="col-md-6 d-none d-md-block">
                        <img src="images/carousel/szkolenia_dla_rad_pedagogicznych_04.png" class="img-fluid rounded shadow-lg" alt="Rady pedagogiczne">
                    </div>
                </div>
            </div>
        </div>
        <div class="carousel-item" style="height: 400px; background: linear-gradient(135deg, #0b132b 0%, #1c2541 100%);">
            <div class="container h-100">
                <div class="row h-100 align-items-center">
                    <div class="col-md-6 text-white">
                        <h1 class="display-4 fw-bold mb-4">Bezpłatne webinary</h1>
                        <p class="lead mb-4">Dołącz do tysięcy nauczycieli korzystających z cotygodniowych darmowych szkoleń. Praktyka, inspiracja i nowoczesne podejście do edukacji.</p>
                        <a href="#courses" class="btn btn-light btn-lg">Zapisz się teraz</a>
                    </div>
                    <div class="col-md-6 d-none d-md-block">
                        <img src="images/carousel/bezplatne_webinary_01.png" class="img-fluid rounded shadow-lg" alt="Bezpłatne webinary">
                    </div>
                </div>
            </div>
        </div>
        <div class="carousel-item" style="height: 400px; background: linear-gradient(135deg, #061a40 0%, #0b2b63 100%);">
            <div class="container h-100">
                <div class="row h-100 align-items-center">
                    <div class="col-md-6 text-white">
                        <h1 class="display-4 fw-bold mb-4">TIK w pracy NAUCZYCIELA</h1>
                        <p class="lead mb-4">Szkolenia z wykorzystania technologii informacyjnych w edukacji to konkretne narzędzia, gotowe scenariusze i sprawdzone rozwiązania.</p>
                        <a href="#courses" class="btn btn-light btn-lg">Poznaj szczegóły</a>
                    </div>
                    <div class="col-md-6 d-none d-md-block">
                        <img src="images/carousel/tik_w_pracy_nauczyciela_01.png" class="img-fluid rounded shadow-lg" alt="TIK w pracy nauczyciela">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Poprzedni</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Następny</span>
    </button>
</div>

<!-- ===== UPCOMING COURSES ======================================= -->
<section id="courses" class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-6">
                <div class="badge bg-warning text-dark mb-2">Nadchodzące wydarzenia</div>
                <h2 class="display-5 fw-bold mb-3">Szkolenia, które <span class="text-primary">rozwijają</span></h2>
                <p class="lead">Zapoznaj się z naszymi najbliższymi szkoleniami i wybierz te, które najlepiej odpowiadają Twoim potrzebom zawodowym.</p>
            </div>
            <div class="col-lg-6 d-flex align-items-end justify-content-lg-end">
                <a href="https://nowoczesna-edukacja.pl" target="_blank" class="btn btn-outline-primary rounded-pill px-4">Zobacz wszystkie szkolenia</a>
            </div>
        </div>
        
        <div class="row row-cols-1 row-cols-md-3 g-4" data-aos="fade-up">
            @foreach($courses as $course)
                <div class="col">
                    <div class="card h-100 border-0 shadow-sm hover-lift">
                        <div class="position-relative">
                            @if(!empty($course->image))
                                <img src="{{ 'https://adm.pnedu.pl/storage/' . ltrim($course->image, '/') }}" class="card-img-top" alt="{{ $course->title }}">
                            @else
                                <div class="card-img-top d-flex align-items-center justify-content-center mb-2" style="width:100%; aspect-ratio:1/1; background:#e9ecef; border: 2px solid #dee2e6; border-radius: .5rem; border-style:dashed;">
                                    <i class="bi bi-mortarboard" style="font-size: 4rem; color: #f8f9fa;"></i>
                                </div>
                            @endif
                        </div>
                        <div class="card-body d-flex flex-column p-4">
                            <h5 class="card-title fw-bold mb-3">{{ $course->title }}</h5>
                            @php
                                $start = \Carbon\Carbon::parse($course->start_date)->locale('pl');
                                $end = $course->end_date ? \Carbon\Carbon::parse($course->end_date) : null;
                            @endphp
                            <ul class="list-unstyled mb-3">
                                <li><strong>Data:</strong> {{ $start->format('d.m.Y') }}</li>
                                <li><strong>Godzina:</strong> {{ $start->format('H:i') }}@if($end) ({{ $start->diffInMinutes($end) }} min)@endif</li>
                                <li><strong>Dzień tygodnia:</strong> {{ $start->translatedFormat('l') }}</li>
                            </ul>
                            <p class="card-text">
                                <strong>Trener:</strong> {{ $course->trainer }}
                            </p>
                            <div class="mt-auto pt-3">
                                <a href="{{ $course->registration_url ?? '#' }}" target="_blank"
                                   class="btn btn-warning w-100 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm cta-btn"
                                   style="font-size:1.15rem; letter-spacing:0.5px;">
                                    <span>Zapisz się</span>
                                    <i class="bi bi-arrow-right-circle-fill"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ===== FEATURED SECTION ====================================== -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <div class="badge bg-primary text-white mb-3">Odkryj nasze możliwości</div>
                <h2 class="display-5 fw-bold mb-4">Wsparcie dla nauczycieli <span class="text-primary">na każdym poziomie</span></h2>
                <p class="lead mb-5">Oferujemy kompleksowe wsparcie dla nauczycieli i placówek edukacyjnych. Wszystkie nasze szkolenia są prowadzone przez doświadczonych praktyków i dostosowane do aktualnych potrzeb edukacji.</p>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 d-inline-flex mb-3">
                            <i class="bi bi-mortarboard fs-1 text-primary"></i>
                        </div>
                        <h3 class="h5 fw-bold">Szkolenia dla nauczycieli</h3>
                        <p class="text-muted">Praktyczne warsztaty rozwijające kompetencje metodyczne i cyfrowe</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 d-inline-flex mb-3">
                            <i class="bi bi-briefcase fs-1 text-primary"></i>
                        </div>
                        <h3 class="h5 fw-bold">Wsparcie dla dyrektorów</h3>
                        <p class="text-muted">Szkolenia z zarządzania placówką i prawa oświatowego</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 d-inline-flex mb-3">
                            <i class="bi bi-laptop fs-1 text-primary"></i>
                        </div>
                        <h3 class="h5 fw-bold">Technologie w edukacji</h3>
                        <p class="text-muted">Nowoczesne narzędzia cyfrowe wspierające proces nauczania</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== STATS SECTION WITH COUNTER (CLEAN & LOW HEIGHT) ============================= -->
<section class="py-3" style="background: #f6f8fa;">
    <div class="container">
        <div class="row text-center g-4 align-items-center" data-aos="fade-up">
            <div class="col-6 col-md-3">
                <div class="display-5 fw-bold mb-1" style="color:#0056b3;">
                    <span class="counter" data-target="10000">0</span>+
                </div>
                <p class="text-secondary fw-light small">Przeszkolonych nauczycieli</p>
            </div>
            <div class="col-6 col-md-3">
                <div class="display-5 fw-bold mb-1" style="color:#0056b3;">
                    <span class="counter" data-target="200">0</span>+
                </div>
                <p class="text-secondary fw-light small">Webinarów rocznie</p>
            </div>
            <div class="col-6 col-md-3">
                <div class="display-5 fw-bold mb-1" style="color:#0056b3;">
                    ★<span class="counter" data-target="4.9">0</span>
                </div>
                <p class="text-secondary fw-light small">Średnia ocena</p>
            </div>
            <div class="col-6 col-md-3">
                <div class="display-5 fw-bold mb-1" style="color:#0056b3;">
                    <span class="counter" data-target="100">0</span>%
                </div>
                <p class="text-secondary fw-light small">Certyfikowanych szkoleń</p>
            </div>
        </div>
    </div>
</section>


<!-- ===== WHY CHOOSE US =========================================== -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <div class="badge bg-success text-white mb-2">Nasze przewagi</div>
                <h2 class="display-5 fw-bold mb-3">Dlaczego warto z nami <span class="text-primary">współpracować?</span></h2>
                <p class="lead mb-0">Stawiamy na jakość, praktyczność i wsparcie na każdym etapie współpracy.</p>
            </div>
        </div>
        
        <div class="row g-4" data-aos="fade-up">
            <div class="col-md-3 col-6">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="display-4 mb-3 text-primary">📄</div>
                        <h5 class="fw-bold mb-2">Certyfikaty w 24h</h5>
                        <p class="text-muted small mb-0">Natychmiastowy dostęp po ukończeniu szkolenia</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="display-4 mb-3 text-primary">🎓</div>
                        <h5 class="fw-bold mb-2">Akredytacja MEN</h5>
                        <p class="text-muted small mb-0">Uznanie zawodowe i rozwój kariery</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="display-4 mb-3 text-primary">💳</div>
                        <h5 class="fw-bold mb-2">Elastyczne płatności</h5>
                        <p class="text-muted small mb-0">Raty i faktury dla szkół</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body p-4 text-center">
                        <div class="display-4 mb-3 text-primary">💡</div>
                        <h5 class="fw-bold mb-2">Wsparcie TIK</h5>
                        <p class="text-muted small mb-0">Praktyczne narzędzia i konsultacje</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== TESTIMONIALS ============================================ -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5">
            <div class="col-lg-6 mx-auto text-center">
                <div class="badge bg-warning text-dark mb-2">Co mówią uczestnicy</div>
                <h2 class="display-5 fw-bold mb-3">Opinie <span class="text-primary">uczestników</span></h2>
                <p class="lead">Poznaj doświadczenia nauczycieli, którzy skorzystali z naszych szkoleń.</p>
            </div>
        </div>
        
        <div class="row g-4 justify-content-center" data-aos="fade-up">
            <div class="col-md-5">
                <div class="card border-0 shadow h-100 p-4">
                    <div class="d-flex mb-4">
                        <i class="bi bi-star-fill text-warning me-1"></i>
                        <i class="bi bi-star-fill text-warning me-1"></i>
                        <i class="bi bi-star-fill text-warning me-1"></i>
                        <i class="bi bi-star-fill text-warning me-1"></i>
                        <i class="bi bi-star-fill text-warning"></i>
                    </div>
                    <p class="fs-5 mb-4">„Szkolenie było bardzo profesjonalne i konkretne. Materiały świetnie przygotowane, a prowadzący wspaniale tłumaczył."</p>
                    <div class="d-flex align-items-center mt-auto">
                        <img src="https://placehold.co/70x70" alt="Anna Nowak" class="rounded-circle me-3">
                        <div>
                            <h6 class="fw-bold mb-1">Anna Nowak</h6>
                            <small class="text-muted">Nauczycielka, Kraków</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card border-0 shadow h-100 p-4">
                    <div class="d-flex mb-4">
                        <i class="bi bi-star-fill text-warning me-1"></i>
                        <i class="bi bi-star-fill text-warning me-1"></i>
                        <i class="bi bi-star-fill text-warning me-1"></i>
                        <i class="bi bi-star-fill text-warning me-1"></i>
                        <i class="bi bi-star-fill text-warning"></i>
                    </div>
                    <p class="fs-5 mb-4">„Dzięki szkoleniu z AI potrafię szybciej przygotować materiały i lepiej reagować na potrzeby uczniów."</p>
                    <div class="d-flex align-items-center mt-auto">
                        <img src="https://placehold.co/70x70" alt="Piotr Zieliński" class="rounded-circle me-3">
                        <div>
                            <h6 class="fw-bold mb-1">Piotr Zieliński</h6>
                            <small class="text-muted">Wicedyrektor, Wrocław</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>    

<!-- ===== NEWSLETTER STRIP ======================================== -->
<section class="py-5 bg-primary bg-gradient">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 text-center text-lg-start text-white mb-4 mb-lg-0">
                <h2 class="h3 fw-bold mb-2">Zapisz się do newslettera</h2>
                <p class="mb-0">Otrzymuj informacje o nowościach i promocjach</p>
            </div>
            <div class="col-lg-6">
                <form class="needs-validation d-flex gap-2">
                    <input type="email" class="form-control" placeholder="Twój e-mail" required>
                    <button type="submit" class="btn btn-warning px-4">Zapisz się</button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- ===== CONTACT FORM ============================================ -->
<section id="kontakt" class="py-5">
    <div class="container">
        <div class="row justify-content-between">
            <div class="col-lg-5 mb-5 mb-lg-0">
                <div class="badge bg-info text-white mb-2">Skontaktuj się z nami</div>
                <h2 class="display-5 fw-bold mb-4">Masz pytania?<br><span class="text-primary">Napisz do nas</span></h2>
                <p class="lead mb-4">Jesteśmy gotowi odpowiedzieć na wszystkie Twoje pytania dotyczące szkoleń, współpracy lub innych kwestii.</p>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-telephone text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Zadzwoń do nas</h6>
                        <p class="mb-0">+48 501 654 274</p>
                    </div>
                </div>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-envelope text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Email</h6>
                        <p class="mb-0">kontakt@nowoczesna-edukacja.pl</p>
                    </div>
                </div>
                
                <div class="d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                        <i class="bi bi-geo-alt text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Adres</h6>
                        <p class="mb-0">ul. A. Zamoyskiego 30/14, 09-320 Bieżuń</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg">
                    <div class="card-body p-4 p-md-5">
                        <form method="POST" action="{{ route('contact.send') }}">
                            @csrf
                            <div class="mb-4">
                                <label for="name" class="form-label fw-semibold">Imię i nazwisko</label>
                                <input type="text" class="form-control form-control-lg border-0 bg-light" id="name" name="name" required>
                            </div>
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">Adres e-mail</label>
                                <input type="email" class="form-control form-control-lg border-0 bg-light" id="email" name="email" required>
                            </div>
                            <div class="mb-4">
                                <label for="message" class="form-label fw-semibold">Wiadomość</label>
                                <textarea class="form-control form-control-lg border-0 bg-light" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="consent" name="consent" required>
                                <label class="form-check-label" for="consent">Wyrażam zgodę na przetwarzanie danych osobowych zgodnie z polityką prywatności.</label>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100">Wyślij wiadomość</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


@endsection

@push('styles')
<style>
    :root {
        --primary-color: #0d6efd;
        --primary-dark: #0b5ed7;
        --secondary-color: #6c757d;
        --success-color: #198754;
        --info-color: #0dcaf0;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --light-color: #f8f9fa;
        --dark-color: #212529;
    }

    body {
        font-family: 'Inter', sans-serif;
        color: #333;
    }

    .hover-lift {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
    }

    .carousel-item {
        overflow: hidden;
    }

    .carousel-item img {
        object-fit: cover;
        object-position: center;
    }

    .carousel-fade .carousel-item {
        opacity: 0;
        transition-duration: .6s;
        transition-property: opacity;
    }

    .carousel-fade .carousel-item.active {
        opacity: 1;
    }

    .badge {
        font-weight: 500;
        letter-spacing: 0.5px;
        padding: 0.5em 1em;
    }

    .card {
        transition: all 0.3s ease;
        border-radius: 10px;
        overflow: hidden;
    }

    .form-control {
        padding: 0.75rem 1rem;
    }

    .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    }

    .btn {
        padding: 0.6rem 1.5rem;
        font-weight: 500;
        letter-spacing: 0.3px;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
        transform: translateY(-2px);
    }

    .rounded-circle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
    }

    /* Counters animation */
    @keyframes countUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .counter {
        animation: countUp 1s forwards;
    }

    .cta-btn {
        transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    }
    .cta-btn:hover, .cta-btn:focus {
        background: #e0a800 !important;
        color: #212529 !important;
        box-shadow: 0 2px 12px rgba(224,168,0,0.18);
    }
    .cta-btn:hover i, .cta-btn:focus i {
        transform: translateX(6px) scale(1.1);
        transition: transform 0.18s;
    }
    .cta-btn i {
        transition: transform 0.18s;
    }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    // Initialize AOS
    AOS.init({ 
        duration: 800, 
        once: true,
        offset: 100
    });

    // Counter Animation
    document.addEventListener('DOMContentLoaded', () => {
        const counters = document.querySelectorAll('.counter');
        
        const runCounter = (counter) => {
            const target = parseFloat(counter.dataset.target);
            const isDecimal = target % 1 !== 0;
            let current = 0;
            const increment = target / 50; // Speed up the animation
            const duration = 1500; // Total animation duration in ms
            const stepTime = duration / (target / increment);

            const update = () => {
                current += increment;
                if (current < target) {
                    counter.textContent = isDecimal ? current.toFixed(1) : Math.floor(current).toLocaleString('pl-PL');
                    setTimeout(update, stepTime);
                } else {
                    counter.textContent = isDecimal ? target.toFixed(1) : target.toLocaleString('pl-PL');
                }
            };
            
            update();
        };

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    runCounter(entry.target);
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        counters.forEach(counter => observer.observe(counter));
    });

    // Enhanced carousel autoplay and transitions
    const heroCarousel = document.querySelector('#heroCarousel');
    if (heroCarousel) {
        const carousel = new bootstrap.Carousel(heroCarousel, {
            interval: 6000,
            pause: 'hover'
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (!targetElement) return;
            
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
        });
    });
</script>
@endpush