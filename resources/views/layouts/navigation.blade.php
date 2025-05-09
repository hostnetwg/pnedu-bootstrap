{{-- resources/views/layouts/navigation.blade.php --}}
<nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ url('/') }}">
            {{ config('app.name', 'Platforma Nowoczesnej Edukacji') }}
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Left Side Of Navbar -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#hero">Start</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#courses">Szkolenia</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#blog">Blog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#kontakt">Kontakt</a>
                </li>
            </ul>

            <!-- Right Side Of Navbar -->
            <ul class="navbar-nav ms-auto">
                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">{{ __('Logowanie') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">{{ __('Rejestracja') }}</a>
                    </li>
                @else
                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                           data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            {{ Auth::user()->name }}
                        </a>

                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                {{ __('Profil') }}
                            </a>
                            <a class="dropdown-item" href="{{ route('dashboard') }}">
                                {{ __('Panel użytkownika') }}
                            </a>
                            <hr class="dropdown-divider">
                            <a class="dropdown-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                {{ __('Wyloguj') }}
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>
