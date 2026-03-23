{{--
  Wymaga: $participants, $szkoleniaTyp
  Opcjonalnie: $szkoleniaFilterRoute — nazwa trasy dla linków filtra (domyślnie dashboard.szkolenia)
--}}
@php
    $szkoleniaTyp = $szkoleniaTyp ?? 'all';
    $szkoleniaFilterRoute = $szkoleniaFilterRoute ?? 'dashboard.szkolenia';
@endphp
<div class="d-flex flex-wrap align-items-center gap-2 mb-3" role="group" aria-label="Filtr rodzaju szkolenia">
    <span class="text-muted small me-1">Pokaż:</span>
    <div class="btn-group btn-group-sm" role="group">
        <a href="{{ route($szkoleniaFilterRoute, ['typ' => 'all']) }}"
           class="btn btn-outline-primary @if($szkoleniaTyp === 'all') active @endif">Wszystkie</a>
        <a href="{{ route($szkoleniaFilterRoute, ['typ' => 'paid']) }}"
           class="btn btn-outline-primary @if($szkoleniaTyp === 'paid') active @endif">Płatne</a>
        <a href="{{ route($szkoleniaFilterRoute, ['typ' => 'free']) }}"
           class="btn btn-outline-primary @if($szkoleniaTyp === 'free') active @endif">Bezpłatne</a>
    </div>
</div>
<p class="text-muted mb-4">
    <small>Liczba szkoleń: <strong>{{ $participants->total() }}</strong></small>
</p>

@if($participants->isEmpty())
    <p class="text-muted">
        @if($szkoleniaTyp === 'all')
            Nie jesteś jeszcze zarejestrowany na żadne szkolenie.
        @else
            Brak szkoleń w wybranej kategorii.
        @endif
    </p>
@else
    <div class="training-list">
        @foreach($participants as $participant)
            @php
                $course = $participant->course;
                $firstVideo = $course?->videos->first();
                $accessActive = $participant->hasActiveAccess();
                $accessExpiresFormatted = $participant->access_expires_at
                    ? $participant->access_expires_at->timezone(config('app.timezone'))->format('Y-m-d H:i')
                    : null;
            @endphp
            <div class="training-item">
                <div class="training-content">
                    <h3 class="training-title mb-2">
                        @if($firstVideo && $accessActive)
                            <a href="{{ route('dashboard.szkolenia.wideo', $participant) }}" class="training-title-link" title="Otwórz nagranie">
                                {{ $course?->title ?? 'Szkolenie niedostępne w katalogu' }}
                                <i class="bi bi-camera-video ms-1" style="font-size: 0.9em; opacity: 0.7;"></i>
                            </a>
                        @elseif($firstVideo && !$accessActive)
                            <span class="training-title-link training-title-link--disabled" title="Dostęp wygasł">
                                {{ $course?->title ?? 'Szkolenie niedostępne w katalogu' }}
                                <i class="bi bi-camera-video-off ms-1 text-muted" style="font-size: 0.9em;"></i>
                            </span>
                        @else
                            <span class="training-title-text">{{ $course?->title ?? 'Szkolenie niedostępne w katalogu' }}</span>
                        @endif
                    </h3>
                    @if($course)
                        <div class="training-meta mb-2">
                            <span class="training-date">
                                Data:
                                @if($course->start_date)
                                    {{ \Carbon\Carbon::parse($course->start_date)->format('d.m.Y') }}
                                @else
                                    <span class="text-muted">Brak daty</span>
                                @endif
                            </span>
                            <span class="training-separator">|</span>
                            <span class="training-instructor">
                                {{ $course->trainer_title }}:
                                @if($course->instructor)
                                    {{ $course->instructor->full_name }}
                                    @if($course->instructor->title)
                                        <span class="text-muted">({{ $course->instructor->title }})</span>
                                    @endif
                                @else
                                    <span class="text-muted">Brak prowadzącego</span>
                                @endif
                            </span>
                        </div>
                    @endif
                    <p class="training-access-term text-muted small mb-0">
                        @if($participant->access_expires_at)
                            @if($accessActive)
                                Dostęp wygaśnie {{ $accessExpiresFormatted }}
                            @else
                                Dostęp wygasł {{ $accessExpiresFormatted }}
                            @endif
                        @else
                            Dostęp bezterminowy
                        @endif
                    </p>
                </div>
                @if($course)
                    @php
                        $courseForCert = $course;
                        $certStatusKey = $courseForCert->certificate_download_status ?? 'in_preparation';
                        $certCanDownload = $certStatusKey === 'download_enabled';
                        $certCourseEnded = $courseForCert->end_date && \Carbon\Carbon::parse($courseForCert->end_date)->isPast();
                        $zaswiadczenieUrl = route('dashboard.zaswiadczenia.course', $courseForCert->id).'?from=szkolenia';
                    @endphp
                    <div class="training-certificate">
                        @if(!$certCourseEnded)
                            <span class="certificate-download-link certificate-download-link--disabled"
                                  title="Zaświadczenie zostanie udostępnione po zakończeniu szkolenia">
                                <img src="{{ asset('images/certificate.png') }}"
                                     alt=""
                                     class="certificate-icon certificate-icon--muted"
                                     aria-hidden="true">
                                <span class="visually-hidden">Po zakończeniu szkolenia</span>
                            </span>
                        @elseif($certCanDownload)
                            <a href="{{ $zaswiadczenieUrl }}"
                               class="certificate-download-link"
                               title="Zaświadczenie — szczegóły i pobranie (jak w zakładce Zaświadczenia)">
                                <img src="{{ asset('images/certificate.png') }}"
                                     alt="Zaświadczenie"
                                     class="certificate-icon">
                            </a>
                        @elseif($certStatusKey === 'no_certificate')
                            <span class="certificate-download-link certificate-download-link--disabled"
                                  title="Brak zaświadczenia dla tego szkolenia">
                                <img src="{{ asset('images/certificate.png') }}"
                                     alt=""
                                     class="certificate-icon certificate-icon--muted"
                                     aria-hidden="true">
                                <span class="visually-hidden">Brak zaświadczenia</span>
                            </span>
                        @else
                            <span class="certificate-download-link certificate-download-link--disabled"
                                  title="Zaświadczenie w przygotowaniu — gdy będzie gotowe, pojawi się tutaj i w zakładce Zaświadczenia">
                                <img src="{{ asset('images/certificate.png') }}"
                                     alt=""
                                     class="certificate-icon certificate-icon--muted"
                                     aria-hidden="true">
                                <span class="visually-hidden">W przygotowaniu</span>
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if($participants->hasPages())
        <div class="mt-4 d-flex justify-content-center">
            {{ $participants->links('pagination::bootstrap-4') }}
        </div>
    @endif
@endif
