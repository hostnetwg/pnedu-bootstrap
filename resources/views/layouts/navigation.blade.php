{{--
    navigation.blade.php – pasek nawigacyjny (Bootstrap 5)
    • Lewy segment: Start + Szkolenia otwarte / zamknięte + Blog + Kontakt.
    • Prawy segment: autoryzacja (Logowanie / Rejestracja lub menu użytkownika).
    • Hiperłącza pozostają na "#" – właściwe route() dodasz później.
--}}

<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container">
        {{-- Logo – zawsze prowadzi na stronę główną --}}
        <a class="navbar-brand fw-bold" href="#">Platforma&nbsp;Nowoczesnej&nbsp;Edukacji</a>

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
                <li class="nav-item"><a class="nav-link" href="#">Start</a></li>

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
                <li class="nav-item"><a class="nav-link" href="#">Blog</a></li>
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
                            <li><a class="dropdown-item" href="#">Panel użytkownika</a></li>
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
