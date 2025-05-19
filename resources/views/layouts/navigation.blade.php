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
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- Główne menu --}}
        <div class="collapse navbar-collapse" id="mainNavbar">
            {{-- Lewa strona paska --}}
            <ul class="navbar-nav me-auto mb-2 mb-lg-0"><!-- me-auto = elementy doklejone do lewej -->

                {{-- Start --}}
                <li class="nav-item"><a  href="{{ route('home') }}" class="nav-link" href="#">Start</a></li>

                {{-- SZKOLENIA OTWARTE (dropdown) --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="openTrainingDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        Szkolenia otwarte
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="openTrainingDropdown">
                        <li><a class="dropdown-item" href="#">Webinar</a></li>
                        <li><a class="dropdown-item" href="#">Szkolenie online LIVE</a></li>
                        <li><a class="dropdown-item" href="#">Warsztat online LIVE</a></li>
                        <li><a class="dropdown-item" href="#">Kurs online (asynchroniczny)</a></li>
                    </ul>
                </li>

                {{-- SZKOLENIA ZAMKNIĘTE (dropdown) --}}
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="closedTrainingDropdown" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        Szkolenia zamknięte
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="closedTrainingDropdown">
                        <li><a class="dropdown-item" href="#">Szkolenie online LIVE (dedykowane)</a></li>
                        <li><a class="dropdown-item" href="#">Warsztat online LIVE (dedykowany)</a></li>
                        <li><a class="dropdown-item" href="#">Szkolenie stacjonarne – wykład</a></li>
                        <li><a class="dropdown-item" href="#">Warsztat stacjonarny</a></li>
                    </ul>
                </li>

                {{-- Blog i Kontakt (również po lewej) --}}
                <li class="nav-item"><a class="nav-link" href="{{ route('blog.index') }}">Blog</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Kontakt</a></li>
            </ul>

            {{-- Prawa strona paska – tylko autoryzacja --}}
            <ul class="navbar-nav mb-2 mb-lg-0"><!-- brak me-auto => doklejone do prawej -->
                @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ Auth::user()->name }}
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
