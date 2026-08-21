{{-- Wspólny przycisk dołączenia do spotkania (homepage + Moje szkolenia) --}}
@php
    /** @var \App\Support\DashboardCourseLiveAccess $live */
    $joinLabel = $joinLabel ?? 'Dołącz do spotkania na żywo';
    $embedLabel = $embedLabel ?? 'Dołącz do spotkania na żywo';
    $joinUnlocked = $live->joinUnlocked;
    $wrapperClass = trim('d-inline-flex flex-wrap gap-2 align-items-center '.($wrapperClass ?? ''));
    $buttonClass = trim('btn btn-success btn-sm'.($joinUnlocked ? '' : ' disabled pe-none').' '.($buttonClass ?? ''));
    $embedButtonClass = trim('btn btn-success btn-sm'.($joinUnlocked ? '' : ' disabled pe-none').' '.($embedButtonClass ?? ''));
@endphp
<span class="{{ $wrapperClass }}">
    @if($live->clickmeetingJoinEnabled && $live->joinUrl)
    <span
      @unless($joinUnlocked)
          tabindex="0"
          data-bs-toggle="tooltip"
          data-bs-placement="top"
          title="{{ $live->joinUnlockHint }}"
          data-live-join-tooltip-wrap
      @endunless>
        <a @if($joinUnlocked)
               href="{{ $live->joinUrl }}"
               target="_blank"
               rel="noopener noreferrer"
           @else
               role="link"
               aria-disabled="true"
               tabindex="-1"
           @endif
           class="{{ $buttonClass }}"
           data-live-join-btn
           data-join-url="{{ $live->joinUrl }}"
           data-join-unlock-at="{{ $live->joinUnlockAtIso }}"
           @if($joinUnlocked) data-join-unlocked="1" @endif>
            <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>
            {{ $joinLabel }}
        </a>
    </span>
    @endif

    @if($live->embedEnabled && $live->embedUrl)
        <span
          @unless($joinUnlocked)
              tabindex="0"
              data-bs-toggle="tooltip"
              data-bs-placement="top"
              title="{{ $live->joinUnlockHint }}"
          @endunless>
            <a @if($joinUnlocked)
                   href="{{ $live->embedUrl }}"
                   data-cm-embed-autofullscreen="1"
               @else
                   role="link"
                   aria-disabled="true"
                   tabindex="-1"
               @endif
               class="{{ $embedButtonClass }}"
               data-live-embed-btn
               data-embed-url="{{ $live->embedUrl }}"
               data-join-unlock-at="{{ $live->joinUnlockAtIso }}"
               @if($joinUnlocked) data-join-unlocked="1" @endif>
                <i class="bi bi-display me-1" aria-hidden="true"></i>
                {{ $embedLabel }}
            </a>
        </span>
    @endif
</span>
