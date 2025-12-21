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
                        <li><a class="dropdown-item" href="#">Nasza misja</a></li>
                        <li><a class="dropdown-item" href="{{ route('about.team') }}">Nasz zespół</a></li>
                        <li><a class="dropdown-item" href="#">Akredytacja MKO</a></li>
                    </ul>
                </li>

                {{-- Kontakt --}}
                <li class="nav-item"><a class="nav-link" href="#">Kontakt</a></li>
            </ul>

            {{-- Prawa strona paska – tylko autoryzacja --}}
            <ul class="navbar-nav mb-2 mb-lg-0"><!-- brak me-auto => doklejone do prawej -->
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> {{ Auth::user()->first_name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a href="{{ route('dashboard') }}" class="dropdown-item" href="#">Panel użytkownika</a></li>
                            <li><a href="{{ route('profile.edit') }}" class="dropdown-item" href="#">Edytuj profil</a></li>                            
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
