@extends('layouts.app')

@section('title', 'Akredytacja Mazowieckiego Kuratora Oświaty – O nas – ' . config('app.name'))

@section('content')

<div class="bg-primary bg-gradient text-white py-3 text-center">
    <div class="container">
        <p class="lead fw-semibold mb-0">
            Akredytacja MKO<br>
            <span style="color: #c6a300; font-style: normal; font-weight: 600;">
                Decyzja Mazowieckiego Kuratora Oświaty
            </span>
        </p>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="h2 fw-bold mb-4">Akredytacja Mazowieckiego Kuratora Oświaty</h1>

                <p class="lead text-muted mb-4">
                    Niepubliczny Ośrodek Doskonalenia Nauczycieli „Platforma Nowoczesnej Edukacji” prowadzi działalność na
                    podstawie ważnej <strong>akredytacji Mazowieckiego Kuratora Oświaty</strong>. To dla nas i dla Państwa
                    placówek potwierdzenie, że spełniamy wymogi prawne oraz standardy jakościowe właściwe dla placówek
                    doskonalenia nauczycieli.
                </p>

                <h2 class="h5 fw-semibold mt-4 mb-3">Jakość i spokój formalny przy wyborze szkoleń</h2>
                <p>
                    Stawiamy na <strong>przemyślane programy, doświadczonych prowadzących i przejrzystą organizację</strong>
                    — tak, aby czas spędzony na szkoleniu realnie wspierał pracę nauczycieli i kadry zarządzającej.
                    Współpraca z <strong>akredytowaną placówką</strong> ułatwia szkołom i przedszkolom uzasadnienie wyboru
                    usług rozwojowych oraz ich rozliczenie w ramach środków przeznaczonych przez
                    <strong>organ prowadzący</strong> na doskonalenie zawodowe. Dzięki temu dyrektorzy i rady pedagogiczne
                    mogą planować rozwój kadry z większą <strong>pewnością co do możliwości sfinansowania</strong> wybranych
                    form podnoszenia kwalifikacji z budżetu jednostki — zgodnie z obowiązującymi zasadami i procedurami u
                    Państwa organu.
                </p>

                <h2 class="h5 fw-semibold mt-4 mb-3">Dokument potwierdzający akredytację</h2>
                <p class="mb-0">
                    Poniżej udostępniamy skan decyzji o udzieleniu akredytacji na okres <strong>2025–2030</strong>.
                    Możesz go przeglądać w przeglądarce lub pobrać w formacie PDF.
                </p>

                @if($pdfAvailable ?? false)
                    <div class="d-flex flex-wrap gap-2 my-4">
                        <a href="{{ $pdfUrl }}" class="btn btn-primary" download>
                            <i class="bi bi-download me-1"></i> Pobierz decyzję (PDF)
                        </a>
                        <a href="{{ $pdfUrl }}" class="btn btn-outline-primary" target="_blank" rel="noopener">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Otwórz PDF w nowej karcie
                        </a>
                    </div>

                    <div class="border rounded-3 overflow-hidden shadow-sm bg-light" style="min-height: 70vh;">
                        <iframe
                            title="Decyzja MKO – akredytacja (podgląd PDF)"
                            src="{{ $pdfUrl }}#toolbar=1"
                            class="w-100 border-0 d-block"
                            style="min-height: 75vh;"
                            loading="lazy"
                        ></iframe>
                    </div>
                    <p class="small text-muted mt-2 mb-4">
                        Jeśli podgląd nie wyświetla się w przeglądarce, użyj przycisku „Pobierz decyzję (PDF)” lub
                        „Otwórz PDF w nowej karcie”.
                    </p>
                @else
                    <div class="alert alert-warning mt-4 mb-4" role="alert">
                        <strong>Brak pliku PDF na serwerze.</strong>
                        Skopiuj plik decyzji do katalogu
                        <code>public/documents/decyzja-mko-akredytacja-2025-2030.pdf</code>
                        (np. z pliku „Decyzja MKO - akredytacja 5 lat (2025-2030).pdf”), po czym odśwież tę stronę.
                    </div>
                @endif

                <div class="border border-primary border-opacity-25 rounded-3 p-4 mt-2 mb-0 bg-primary bg-opacity-10">
                    <p class="mb-2 fw-semibold text-primary mb-3">Zapraszamy do zapoznania się z naszą ofertą szkoleń</p>
                    <p class="mb-3 small text-muted">
                        Wybierz format dopasowany do potrzeb placówki — od otwartych webinarów po szkolenia zamknięte i
                        indywidualne konsultacje.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('courses.individual') }}" class="btn btn-primary btn-sm">Szkolenia otwarte i indywidualne</a>
                        <a href="{{ route('courses.free') }}" class="btn btn-outline-primary btn-sm">Webinary bezpłatne</a>
                        <a href="{{ route('home') }}#courses" class="btn btn-outline-secondary btn-sm">Nadchodzące wydarzenia</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
