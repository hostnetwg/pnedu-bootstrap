{{--
    navigation.blade.php – pasek nawigacyjny (Bootstrap 5)
    • Lewy segment: Start + Szkolenia otwarte / zamknięte + Blog + Kontakt.
    • Prawy segment: autoryzacja (Logowanie / Rejestracja lub menu użytkownika).
    • Hiperłącza pozostają na "#" – właściwe route() dodasz później.
--}}

<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm sticky-top">
    <div class="container">
        {{-- Logo – zawsze prowadzi na stronę główną --}}
        <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center fw-bold">
            <img src="{{ asset('images/Logo_PNG.svg') }}"
                alt="Logo Platforma Nowoczesnej Edukacji"
                width="52"
                height="52"
                class="me-2"
                style="object-fit:contain; margin-top: -8px; margin-bottom: -8px;">
            <span>Platforma&nbsp;Nowoczesnej&nbsp;Edukacji</span>
        </a>

        {{-- Burger (mobile) --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Główne menu --}}
        <div class="collapse navbar-collapse" id="mainNavbar">
            {{-- Lewa strona paska --}}
            <ul class="navbar-nav me-auto mb-2 mb-lg-0"><!-- me-auto = elementy doklejone do lewej -->

                {{-- SZKOLENIA OTWARTE (dropdown) --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="openTrainingDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        Szkolenia
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="openTrainingDropdown">
                        <li><a class="dropdown-item" href="{{ route('courses.individual') }}">Szkolenia indywidualne</a></li>
                        <li><a class="dropdown-item" href="#">Szkolenia rad pedagogicznych</a></li>
                    </ul>
                </li>

                {{-- BEZPŁATNE (dropdown) --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="freeDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        Webinary (bezpłatne)
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="freeDropdown">
                        <li><a class="dropdown-item" href="{{ route('courses.free') }}">TIK w pracy NAUCZYCIELA</a></li>
                        <li><a class="dropdown-item" href="{{ route('courses.director-academy') }}">Akademia Dyrektora</a></li>
                        <li><a class="dropdown-item" href="{{ route('courses.office365') }}">Szkolny ADMINISTRATOR Office 365</a></li>
                        <li><a class="dropdown-item" href="{{ route('courses.parent-academy') }}">Akademia Rodzica</a></li>
                    </ul>
                </li>

                {{-- Blog --}}
                <li class="nav-item"><a class="nav-link" href="{{ route('blog.index') }}">Blog</a></li>

                {{-- O NAS (dropdown) --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="aboutDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        O nas
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="aboutDropdown">
                        <li><a class="dropdown-item" href="{{ route('about.team') }}">Nasz zespół</a></li>
                        <li><a class="dropdown-item" href="{{ route('about.accreditation') }}">Akredytacja MKO</a></li>
                    </ul>
                </li>

                {{-- Kontakt --}}
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#kontakt">Kontakt</a></li>
            </ul>

            {{-- Prawa strona paska – tylko autoryzacja --}}
            <ul class="navbar-nav mb-2 mb-lg-0"><!-- brak me-auto => doklejone do prawej -->
                @auth
                    @if(($dashboardMojeZasobyCount ?? 0) > 0)
                        <li class="nav-item d-flex align-items-center">
                            <a class="nav-link nav-moje-zasoby @if(request()->routeIs('dashboard*')) nav-moje-zasoby--active @endif"
                               href="{{ $dashboardTwojeZasobyUrl ?? route('dashboard') }}">
                                <i class="bi bi-grid-1x2-fill me-1" aria-hidden="true"></i>
                                Twoje zasoby ({{ $dashboardMojeZasobyCount }})
                            </a>
                        </li>
                    @endif
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->first_name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a href="{{ route('profile.edit') }}" class="dropdown-item">Edytuj profil</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item" type="submit">Wyloguj</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @else
                    <li class="nav-item"><a class="nav-link" href="{{ route('login') }}">Zaloguj się</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('register') }}">Rejestracja</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<style>
.nav-moje-zasoby {
    margin: 0.2rem 0.35rem 0.2rem 0;
    padding: 0.45rem 0.9rem !important;
    border-radius: 0.5rem;
    font-weight: 600;
    color: #fff !important;
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 55%, #084298 100%);
    transition: background 0.15s ease;
}
.nav-moje-zasoby:hover,
.nav-moje-zasoby:focus-visible {
    color: #fff !important;
    background: linear-gradient(135deg, #0b5ed7 0%, #094bac 55%, #063d8a 100%);
}
.nav-moje-zasoby--active,
.nav-moje-zasoby--active:hover,
.nav-moje-zasoby--active:focus-visible {
    color: #fff !important;
    background: linear-gradient(135deg, #084298 0%, #052c65 100%);
    outline: 2px solid rgba(255, 255, 255, 0.5);
    outline-offset: 0;
}
.nav-moje-zasoby i {
    color: rgba(255, 255, 255, 0.92);
}
@media (max-width: 991.98px) {
    .nav-moje-zasoby {
        margin: 0.35rem 0 0.5rem;
        display: inline-block;
    }
}
</style>
