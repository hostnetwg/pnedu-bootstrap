<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Panel trenera') — Platforma Nowoczesnej Edukacji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    @include('layouts.partials.trainer-preview-banner')
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a href="{{ route('home') }}" class="navbar-brand d-flex align-items-center">
                <img src="{{ asset('images/Logo_PNG.svg') }}"
                    alt="Platforma Nowoczesnej Edukacji"
                    width="42"
                    height="42"
                    style="object-fit: contain; background: #fff; border-radius: 0.4rem; padding: 2px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#trainerNav" aria-controls="trainerNav" aria-expanded="false" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="trainerNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    @if(($trainerSwitchInstructors ?? collect())->isNotEmpty())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="trainerSwitch" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Trenerzy
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="trainerSwitch" style="max-height: 22rem; overflow-y: auto;">
                                @include('layouts.partials.trainer-preview-switch')
                            </ul>
                        </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('trainer.notifications') }}">
                            <i class="bi bi-bell" aria-hidden="true"></i>
                            Powiadomienia
                            @if(($trainerUnreadCount ?? 0) > 0)
                                <span class="badge bg-danger">{{ $trainerUnreadCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link nav-link">Wyloguj</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container py-4 trainer-panel">
        <div class="row">
            <div class="col-lg-3 mb-4 mb-lg-0">
                <nav class="trainer-menu" aria-label="Menu panelu trenera">
                    <div class="trainer-menu__label">Menu</div>
                    <a href="{{ route('trainer.calendar') }}" class="trainer-menu__link @if(request()->routeIs('trainer.calendar')) is-active @endif">
                        <i class="bi bi-calendar-event" aria-hidden="true"></i>
                        <span>Kalendarz</span>
                    </a>
                    <a href="{{ route('trainer.index') }}" class="trainer-menu__link @if(request()->routeIs('trainer.index', 'trainer.cases.show')) is-active @endif">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <span>Zapytania</span>
                    </a>
                </nav>
            </div>
            <div class="col-lg-9">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                @yield('content')
            </div>
        </div>
    </main>
    <style>
        .trainer-menu {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.5rem rgba(33, 37, 41, 0.06);
            padding: 0.75rem;
        }
        .trainer-menu__label {
            font-size: 0.75rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #6c757d;
            padding: 0.35rem 0.75rem 0.55rem;
        }
        .trainer-menu__link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.75rem;
            border-radius: 0.75rem;
            color: #343a40;
            text-decoration: none;
            font-weight: 600;
        }
        .trainer-menu__link i {
            width: 2.1rem;
            height: 2.1rem;
            display: grid;
            place-items: center;
            border-radius: 0.65rem;
            background: #f1f3f5;
            color: #495057;
        }
        .trainer-menu__link:hover {
            background: #f8f9fa;
            color: #0f5132;
        }
        .trainer-menu__link.is-active {
            background: #e8f6ee;
            color: #0f5132;
        }
        .trainer-menu__link.is-active i,
        .trainer-menu__link:hover i {
            background: #198754;
            color: #fff;
        }
        .trainer-sheet {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.5rem rgba(33, 37, 41, 0.06);
            overflow: hidden;
        }
        .trainer-sheet__head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem 1.25rem 0.25rem;
        }
        .trainer-sheet__count {
            min-width: 2rem;
            height: 2rem;
            padding: 0 0.55rem;
            border-radius: 999px;
            background: #e8f6ee;
            color: #0f5132;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .trainer-month {
            padding: 0.85rem 1.25rem 0.35rem;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #6c757d;
        }
        .trainer-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-top: 1px solid #f1f3f5;
            color: inherit;
            text-decoration: none;
        }
        a.trainer-row:hover {
            background: #f8fbf9;
        }
        .trainer-date {
            width: 3.4rem;
            flex: 0 0 3.4rem;
            text-align: center;
            line-height: 1.05;
        }
        .trainer-date strong {
            display: block;
            font-size: 1.35rem;
            color: #0f5132;
        }
        .trainer-date small {
            color: #6c757d;
            text-transform: lowercase;
        }
        .trainer-row__body {
            min-width: 0;
            flex: 1;
        }
        .trainer-row__title {
            display: block;
            font-weight: 600;
            color: #212529;
        }
        .trainer-empty {
            padding: 2.5rem 1.25rem 2rem;
            text-align: center;
            color: #6c757d;
        }
        .trainer-empty i {
            font-size: 1.8rem;
            display: block;
            margin-bottom: 0.5rem;
        }
        .trainer-panel .card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 0.125rem 0.5rem rgba(33, 37, 41, 0.06);
        }
        @media (max-width: 991.98px) {
            .trainer-menu {
                display: flex;
                gap: 0.5rem;
            }
            .trainer-menu__label {
                display: none;
            }
            .trainer-menu__link {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
