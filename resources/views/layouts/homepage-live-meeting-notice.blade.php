{{-- Dyskretny pasek live — tylko homepage, tylko gdy $homepageLiveNotice (najbliższy dzień, wszystkie szkolenia z tego dnia) --}}
@if(! empty($homepageLiveNotice) && count($homepageLiveNotice->items) > 0)
    @php
        /** @var \App\Support\HomepageLiveMeetingNotice $homepageLiveNotice */
    @endphp
    <div class="homepage-live-notice border-bottom bg-success-subtle overflow-hidden" role="region" aria-label="Twoje szkolenia na żywo">
        <div class="container py-2">
            <div class="d-flex flex-column gap-3">
                @foreach($homepageLiveNotice->items as $index => $item)
                    @php
                        /** @var \App\Support\HomepageLiveMeetingItem $item */
                        $live = $item->live;
                    @endphp
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 gap-lg-3 small mw-100{{ $index > 0 ? ' pt-3 border-top border-success-subtle' : '' }}">
                        <div class="d-flex align-items-start gap-2 flex-grow-1 min-w-0 w-100">
                            <i class="bi bi-camera-video-fill text-success flex-shrink-0 mt-1" aria-hidden="true"></i>
                            <div class="min-w-0 flex-grow-1 overflow-hidden">
                                <p class="mb-0 fw-semibold text-success-emphasis text-break">
                                    {{ $item->courseTitle }}
                                </p>
                                <p class="mb-0 text-body-secondary text-break">
                                    Start: <strong class="text-success-emphasis">{{ $item->startDateLabel }}</strong>
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
                            @if($loop->last)
                                <a href="{{ route('dashboard.szkolenia') }}" class="btn btn-outline-success btn-sm">
                                    {{ $homepageLiveNotice->hasMoreLiveCourses || count($homepageLiveNotice->items) > 1 ? 'Wszystkie szkolenia' : 'Moje szkolenia' }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
