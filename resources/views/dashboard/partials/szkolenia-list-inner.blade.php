{{--
  Wymaga: $participants, $szkoleniaTyp
  Opcjonalnie: $szkoleniaFilterRoute, $szkoleniaCounts — nazwa trasy i liczniki filtrów
--}}
@php
    $szkoleniaTyp = $szkoleniaTyp ?? 'all';
    $szkoleniaFilterRoute = $szkoleniaFilterRoute ?? 'dashboard.szkolenia';
    $cAll = $szkoleniaCounts['all'] ?? null;
    $cPaid = $szkoleniaCounts['paid'] ?? null;
    $cFree = $szkoleniaCounts['free'] ?? null;
@endphp
<div class="d-flex flex-wrap align-items-center gap-2 mb-3 js-szkolenia-list-filters" role="group" aria-label="Filtr rodzaju szkolenia">
    <span class="text-muted small me-1">Pokaż:</span>
    <div class="btn-group btn-group-sm" role="group">
        <a href="{{ route($szkoleniaFilterRoute, ['typ' => 'all']) }}"
           class="btn btn-outline-primary @if($szkoleniaTyp === 'all') active @endif">Wszystkie @if($cAll !== null) ({{ $cAll }}) @endif</a>
        <a href="{{ route($szkoleniaFilterRoute, ['typ' => 'paid']) }}"
           class="btn btn-outline-primary @if($szkoleniaTyp === 'paid') active @endif">Płatne @if($cPaid !== null) ({{ $cPaid }}) @endif</a>
        <a href="{{ route($szkoleniaFilterRoute, ['typ' => 'free']) }}"
           class="btn btn-outline-primary @if($szkoleniaTyp === 'free') active @endif">Bezpłatne @if($cFree !== null) ({{ $cFree }}) @endif</a>
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
                $hasVideos = ($course?->videos_count ?? 0) > 0;
                $hasFileLinks = ($course?->file_links_count ?? 0) > 0;
                $certCourseEnded = $course && $course->end_date && \Carbon\Carbon::parse($course->end_date)->isPast();
                $fileLinksUnlocked = $hasFileLinks && $certCourseEnded;
                $hasOnlineMaterials = $hasVideos || $fileLinksUnlocked;
                $accessActive = $participant->hasActiveAccess();
                $accessExpiresFormatted = $participant->access_expires_at
                    ? $participant->access_expires_at->timezone(config('app.timezone'))->format('Y-m-d H:i')
                    : null;
                $courseDisplayTitle = $course
                    ? trim(str_replace(['&nbsp;', "\xc2\xa0"], ' ', strip_tags(html_entity_decode((string) $course->title, ENT_QUOTES | ENT_HTML5, 'UTF-8'))))
                    : 'Szkolenie niedostępne w katalogu';
                $trainingNotesCount = (int) ($participant->training_video_notes_count ?? 0);
            @endphp
            <div class="training-item">
                <div class="training-content">
                    <h3 class="training-title mb-2">
                        @if($hasOnlineMaterials && $accessActive)
                            <a href="{{ route('dashboard.szkolenia.wideo', $participant) }}" class="training-title-link training-title-link--has-access" title="{{ $hasVideos ? 'Odtwórz nagranie wideo' : 'Materiały do pobrania' }}">
                                @if($hasVideos)
                                    <span class="training-title-play-badge training-title-play-badge--leading" aria-hidden="true"><i class="bi bi-play-fill"></i></span>
                                @else
                                    <i class="bi bi-folder2-open training-title-folder-icon--leading" aria-hidden="true"></i>
                                @endif
                                <span>{{ $courseDisplayTitle }}</span>
                            </a>
                        @elseif($hasOnlineMaterials && !$accessActive)
                            @if($hasVideos)
                                <a href="{{ route('dashboard.szkolenia.wideo', $participant) }}" class="training-title-link training-title-link--expired-notes" title="Twoje notatki (dostęp do nagrania wygasł)">
                                    <span class="training-title-play-badge training-title-play-badge--disabled training-title-play-badge--leading" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
                                    <span>{{ $courseDisplayTitle }}</span>
                                </a>
                            @else
                            <span class="training-title-link training-title-link--disabled training-title-link--expired" title="Dostęp wygasł">
                                <i class="bi bi-folder-x training-title-folder-icon--leading" aria-hidden="true"></i>
                                <span>{{ $courseDisplayTitle }}</span>
                            </span>
                            @endif
                        @else
                            <span class="training-title-text">{{ $courseDisplayTitle }}</span>
                        @endif
                    </h3>
                    @if($course)
                        <div class="training-meta mb-2">
                            <span class="training-date">
                                Data:
                                @if($course->start_date)
                                    {{ \Carbon\Carbon::parse($course->start_date)->timezone(config('app.timezone'))->format('d.m.Y G:i') }}
                                @else
                                    <span class="text-muted">Brak daty</span>
                                @endif
                            </span>
                            <span class="training-separator">|</span>
                            <span class="training-instructor">
                                {{ $course->trainer_title }}:
                                @if($course->instructor)
                                    {{ $course->instructor->full_name_with_title }}
                                @else
                                    <span class="text-muted">Brak prowadzącego</span>
                                @endif
                            </span>
                        </div>
                    @endif
                    <p class="training-access-term small mb-0 @if($participant->access_expires_at && ! $accessActive) text-danger @elseif($accessActive) text-success @else text-muted @endif">
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
                    @if($trainingNotesCount > 0 && $hasVideos)
                        <p class="training-notes-indicator small mb-0 mt-2">
                            <a href="{{ route('dashboard.szkolenia.wideo', $participant) }}"
                               class="training-notes-indicator__link d-inline-flex align-items-center gap-1 text-decoration-none"
                               title="Przejdź do notatek do nagrań z tego szkolenia">
                                <i class="bi bi-journal-text" aria-hidden="true"></i>
                                <span>
                                    @if($trainingNotesCount === 1)
                                        Masz zapisaną notatkę
                                    @elseif($trainingNotesCount >= 2 && $trainingNotesCount <= 4)
                                        Masz {{ $trainingNotesCount }} zapisane notatki
                                    @else
                                        Masz {{ $trainingNotesCount }} zapisanych notatek
                                    @endif
                                </span>
                            </a>
                        </p>
                    @endif
                    @if($course && ! $certCourseEnded)
                        @php
                            /** @var \App\Support\DashboardCourseLiveAccess|null $liveAccessUi */
                            $liveAccessUi = $participant->dashboard_live_access ?? null;
                        @endphp
                        @if($liveAccessUi && $liveAccessUi->show)
                            <div class="training-live-access alert alert-primary border-0 mb-0 mt-2 py-3 px-3" role="region" aria-label="Spotkanie na żywo">
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex gap-2 align-items-start">
                                        <i class="bi bi-camera-video flex-shrink-0 mt-1" aria-hidden="true"></i>
                                        <div>
                                            <p class="fw-semibold mb-1">Spotkanie online ({{ $liveAccessUi->platformLabel }})</p>
                                            <p class="small mb-0 lh-base">
                                                Możesz wejść tuż przed startem lub w trakcie szkolenia.
                                            </p>
                                        </div>
                                    </div>
                                    @if($liveAccessUi->countdownTargetIso && $liveAccessUi->countdownLabel)
                                        <div class="training-live-countdown small"
                                             data-live-countdown
                                             data-countdown-target="{{ $liveAccessUi->countdownTargetIso }}"
                                             data-countdown-phase="{{ $liveAccessUi->countdownPhase }}">
                                            <span class="text-body-secondary">{{ $liveAccessUi->countdownLabel }}:</span>
                                            <strong class="js-live-countdown-value ms-1" aria-live="polite">—</strong>
                                        </div>
                                    @endif
                                    @if($liveAccessUi->password)
                                        <p class="small mb-0">
                                            <span class="text-body-secondary">Hasło do spotkania:</span>
                                            <code class="user-select-all">{{ $liveAccessUi->password }}</code>
                                        </p>
                                    @endif
                                    <div>
                                        <a href="{{ $liveAccessUi->joinUrl }}"
                                           class="btn btn-success btn-sm"
                                           target="_blank"
                                           rel="noopener noreferrer">
                                            <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>
                                            Dołącz do spotkania na żywo
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="training-pending-end-notice alert alert-light border mb-0 mt-2 py-2 px-3" role="status">
                            <div class="d-flex gap-2 align-items-start">
                                <i class="bi bi-info-circle flex-shrink-0 text-primary" aria-hidden="true"></i>
                                <p class="small mb-0 lh-base text-body-secondary">
                                    Dostęp do nagrania, materiałów do pobrania oraz możliwość pobrania zaświadczenia pojawią się dopiero po zakończeniu szkolenia.
                                </p>
                            </div>
                        </div>
                    @endif
                    @if($course && $fileLinksUnlocked && $accessActive)
                        <div class="training-materials mt-2 pt-2 border-top">
                            <div class="text-muted small fw-semibold mb-2">
                                <i class="bi bi-folder2-open me-1" aria-hidden="true"></i>Materiały do pobrania
                            </div>
                            <ul class="list-unstyled mb-0 small training-materials-list">
                                @foreach($course->fileLinks as $link)
                                    <li class="mb-1">
                                        <a href="{{ $link->url }}"
                                           class="training-material-link text-decoration-none"
                                           target="_blank"
                                           rel="noopener noreferrer">
                                            @if($link->isGoogleHostedUrl())
                                                <i class="bi bi-google me-1 text-secondary" aria-hidden="true"></i>
                                            @else
                                                <i class="bi bi-link-45deg me-1 text-secondary" aria-hidden="true"></i>
                                            @endif
                                            <span class="training-material-link__label">{{ $link->title ?: $link->url }}</span>
                                            <i class="bi bi-box-arrow-up-right ms-1 align-middle" style="font-size: 0.75em; opacity: 0.65;" aria-hidden="true"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                @if($course)
                    @php
                        $courseForCert = $course;
                        $certStatusKey = $courseForCert->certificate_download_status ?? 'in_preparation';
                        $certCanDownload = $certStatusKey === 'download_enabled';
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
        <div class="mt-4 d-flex justify-content-center js-szkolenia-list-pagination">
            {{ $participants->links('pagination::bootstrap-4') }}
        </div>
    @endif
@endif
