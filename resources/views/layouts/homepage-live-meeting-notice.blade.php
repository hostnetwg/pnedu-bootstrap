{{-- Dyskretny pasek live — tylko homepage, tylko gdy $homepageLiveNotice --}}
@if(! empty($homepageLiveNotice) && $homepageLiveNotice->live->show)
    @php
        /** @var \App\Support\HomepageLiveMeetingNotice $homepageLiveNotice */
        $live = $homepageLiveNotice->live;
    @endphp
    <div class="homepage-live-notice border-bottom bg-success-subtle overflow-hidden" role="region" aria-label="Twoje szkolenie na żywo">
        <div class="container py-2">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 gap-lg-3 small mw-100">
                <div class="d-flex align-items-start gap-2 flex-grow-1 min-w-0 w-100">
                    <i class="bi bi-camera-video-fill text-success flex-shrink-0 mt-1" aria-hidden="true"></i>
                    <div class="min-w-0 flex-grow-1 overflow-hidden">
                        <p class="mb-0 fw-semibold text-success-emphasis text-break">
                            {{ $homepageLiveNotice->courseTitle }}
                        </p>
                        <p class="mb-0 text-body-secondary text-break">
                            Start: <strong class="text-success-emphasis">{{ $homepageLiveNotice->startDateLabel }}</strong>
                            @if($live->platformLabel)
                                <span class="d-none d-sm-inline"> · {{ $live->platformLabel }}</span>
                            @endif
                        </p>
                        @if($live->countdownTargetIso && $live->countdownLabel)
                            <p class="mb-0 text-body-secondary text-break"
                               data-live-countdown
                               data-countdown-target="{{ $live->countdownTargetIso }}"
                               data-countdown-phase="{{ $live->countdownPhase }}">
                                {{ $live->countdownLabel }}:
                                <strong class="js-live-countdown-value text-success-emphasis" aria-live="polite">—</strong>
                            </p>
                        @endif
                        @if($live->password)
                            <p class="mb-0 text-body-secondary text-break">
                                Hasło:
                                <code class="user-select-all">{{ $live->password }}</code>
                            </p>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @include('partials.live-join-button', [
                        'live' => $live,
                        'joinLabel' => 'Dołącz do spotkania',
                    ])
                    <a href="{{ route('dashboard.szkolenia') }}" class="btn btn-outline-success btn-sm">
                        {{ $homepageLiveNotice->hasMoreLiveCourses ? 'Wszystkie szkolenia' : 'Moje szkolenia' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif
