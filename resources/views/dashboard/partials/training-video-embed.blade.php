@php
    $embedUrl = $video->getEmbedUrl();
    $posterUrl = $video->getPosterUrl();
    $videoTitle = $video->title ?: 'Nagranie szkolenia';
@endphp

<div class="training-video-facade ratio ratio-16x9 rounded overflow-hidden bg-secondary position-relative"
     data-embed-url="{{ $embedUrl }}">
    <iframe class="training-video-facade__iframe position-absolute top-0 start-0 w-100 h-100 border-0 d-none"
            title="{{ $videoTitle }}"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
            referrerpolicy="strict-origin-when-cross-origin"></iframe>

    <button type="button"
            class="training-video-facade__poster position-absolute top-0 start-0 w-100 h-100 border-0 p-0 d-flex align-items-center justify-content-center"
            @if($posterUrl) style="background-image: url('{{ e($posterUrl) }}');" @endif
            aria-label="Odtwórz nagranie: {{ $videoTitle }}">
        <span class="training-video-facade__play-badge" aria-hidden="true">
            <i class="bi bi-play-fill"></i>
        </span>
        <span class="visually-hidden">Odtwórz nagranie</span>
    </button>
</div>

@once
@push('styles')
<style>
.training-video-facade__poster {
    background-color: #495057;
    background-size: cover;
    background-position: center;
    cursor: pointer;
    transition: filter 0.2s ease;
}
.training-video-facade__poster:hover,
.training-video-facade__poster:focus-visible {
    filter: brightness(1.05);
}
.training-video-facade__poster:focus-visible {
    outline: 3px solid var(--bs-primary);
    outline-offset: -3px;
}
.training-video-facade__play-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 4.5rem;
    height: 4.5rem;
    border-radius: 50%;
    background: rgba(13, 110, 253, 0.92);
    color: #fff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
    font-size: 2rem;
    line-height: 1;
    padding-left: 0.2rem;
    pointer-events: none;
}
.training-video-facade--playing .training-video-facade__poster {
    display: none !important;
}
.training-video-facade--playing .training-video-facade__iframe {
    display: block !important;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.training-video-facade:not([data-facade-init])').forEach(function (root) {
        root.setAttribute('data-facade-init', '1');

        var poster = root.querySelector('.training-video-facade__poster');
        var iframe = root.querySelector('.training-video-facade__iframe');
        var embedUrl = root.getAttribute('data-embed-url') || '';

        if (!poster || !iframe || !embedUrl) {
            return;
        }

        poster.addEventListener('click', function () {
            if (root.classList.contains('training-video-facade--playing')) {
                return;
            }

            var separator = embedUrl.indexOf('?') >= 0 ? '&' : '?';
            iframe.src = embedUrl + separator + 'autoplay=1&playsinline=1';
            root.classList.add('training-video-facade--playing');
        });
    });
});
</script>
@endpush
@endonce
