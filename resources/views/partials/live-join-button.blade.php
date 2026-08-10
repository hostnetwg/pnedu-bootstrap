{{-- Wspólny przycisk dołączenia do spotkania (homepage + Moje szkolenia) --}}
@php
    /** @var \App\Support\DashboardCourseLiveAccess $live */
    $joinLabel = $joinLabel ?? 'Dołącz do spotkania na żywo';
    $joinUnlocked = $live->joinUnlocked;
    $wrapperClass = trim('d-inline-block '.($wrapperClass ?? ''));
    $buttonClass = trim('btn btn-success btn-sm'.($joinUnlocked ? '' : ' disabled pe-none').' '.($buttonClass ?? ''));
@endphp
<span class="{{ $wrapperClass }}"
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
