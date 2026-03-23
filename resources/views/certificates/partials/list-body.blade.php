{{--
  $items – paginator (mapowane wpisy jak w ParticipantCertificateListService)
  $isDashboardContext – linki do /dashboard/zaswiadczenia/...
  $token – wymagany gdy !$isDashboardContext
  $highlightCourseId – opcjonalnie podświetlenie wiersza (int|null)
  $fromLink – informacja po przekierowaniu z maila (bool)
--}}
@php
    $highlightCourseId = $highlightCourseId ?? null;
    $fromLink = $fromLink ?? false;
@endphp

<div class="{{ $isDashboardContext ? '' : 'container py-5' }}">
    <div class="row justify-content-center">
        <div class="{{ $isDashboardContext ? 'col-12' : 'col-lg-8' }}">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <h1 class="h4 mb-2">Twoje zaświadczenia</h1>
                    <p class="text-muted mb-4">
                        Poniżej lista szkoleń, w których brałeś/aś udział. Zaświadczenie można pobrać, gdy administrator udostępni je dla danego szkolenia.
                    </p>

                    @if($fromLink)
                        <div class="alert alert-info small mb-4">
                            Otwarto z linku w wiadomości e-mail — lista jest taka sama jak w panelu konta.
                        </div>
                    @endif

                    @if($items->isEmpty())
                        <p class="text-muted mb-0">Nie znaleziono żadnych szkoleń powiązanych z Twoim kontem.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($items as $item)
                                <li id="cert-row-{{ $item['course']->id }}"
                                    class="list-group-item d-flex align-items-center justify-content-between py-3 px-0 border-0 border-bottom {{ $highlightCourseId === (int) $item['course']->id ? 'border border-primary rounded px-2 py-3' : '' }}">
                                    <div class="me-3">
                                        <strong class="d-block">
                                            {{
                                                str_replace(
                                                    '&nbsp;',
                                                    ' ',
                                                    html_entity_decode((string) $item['course']->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                                                )
                                            }}
                                        </strong>
                                        <small class="text-muted d-block">
                                            @if($item['course']->start_date)
                                                {{ \Carbon\Carbon::parse($item['course']->start_date)->locale('pl')->translatedFormat('d.m.Y H:i (l)') }}
                                            @else
                                                —
                                            @endif
                                        </small>
                                        @if(!empty($item['course']->trainer) && $item['course']->trainer !== 'Brak trenera')
                                            <small class="text-muted d-block">{{ $item['course']->trainer_title }}: {{ $item['course']->trainer }}</small>
                                        @endif
                                    </div>
                                    <div>
                                        @php
                                            $courseEnded = $item['course']->end_date && \Carbon\Carbon::parse($item['course']->end_date)->isPast();
                                        @endphp
                                        @if(!$courseEnded)
                                            <span class="badge bg-info text-dark" title="Zaświadczenie zostanie udostępnione po zakończeniu szkolenia">Po zakończeniu szkolenia</span>
                                        @elseif($item['can_download'])
                                            @if($isDashboardContext)
                                                <a href="{{ route('dashboard.zaswiadczenia.course', $item['course']->id) }}"
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-download me-1"></i> Pobierz zaświadczenie
                                                </a>
                                            @else
                                                <a href="{{ route('certificates.show-by-token', ['token' => $token, 'course' => $item['course']->id]) }}"
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-download me-1"></i> Pobierz zaświadczenie
                                                </a>
                                            @endif
                                        @elseif(($item['status_key'] ?? '') === 'no_certificate')
                                            <span class="badge bg-dark">Brak zaświadczenia</span>
                                        @else
                                            <span class="badge bg-secondary">W przygotowaniu</span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-4">
                            <p class="text-muted small mb-2 text-center">
                                Wyświetlanie {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} z {{ $items->total() }} wyników
                            </p>
                            <div class="d-flex justify-content-center certificates-pagination">
                                {{ $items->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @if(!$isDashboardContext)
                <p class="text-center text-muted mt-3 small">
                    <a href="{{ route('home') }}" class="text-decoration-none">← Powrót na stronę główną</a>
                </p>
            @endif
        </div>
    </div>
</div>
