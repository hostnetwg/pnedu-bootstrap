@php
    $teaserTitle = $title ?? '';
    $teaserSlug = $slug ?? '#';
    $teaserExcerpt = $excerpt ?? '';
    $teaserDate = $publishedAt ?? null;
    $teaserReadingMinutes = $readingMinutes ?? 1;
    $teaserImageUrl = $imageUrl ?? null;
    $teaserIsExample = $isExample ?? false;
    $showExampleNotice = $showExampleNotice ?? false;
@endphp

<article class="blog-teaser {{ $teaserIsExample ? 'blog-teaser--example' : '' }}">
    @if($teaserIsExample && $showExampleNotice)
        <div class="alert alert-light border mb-4">
            <span class="badge bg-secondary me-2">Przykład układu</span>
            Tak będzie wyglądać zajawka artykułu na pełną szerokość strony po publikacji w panelu.
        </div>
    @endif

    <div class="row g-4 g-lg-5 align-items-center">
        <div class="col-lg-5">
            @if($teaserImageUrl)
                <a href="{{ $teaserIsExample ? '#' : route('blog.show', $teaserSlug) }}" class="d-block blog-teaser__image-link" @if($teaserIsExample) onclick="return false;" @endif>
                    <img src="{{ $teaserImageUrl }}"
                         alt="{{ $teaserTitle }}"
                         class="blog-teaser__image w-100 rounded-3 shadow-sm"
                         loading="lazy">
                </a>
            @else
                <div class="blog-teaser__image blog-teaser__image--placeholder w-100 rounded-3 shadow-sm d-flex align-items-center justify-content-center text-white">
                    <span class="px-3 text-center fw-semibold">{{ $teaserTitle }}</span>
                </div>
            @endif
        </div>

        <div class="col-lg-7">
            <div class="blog-teaser__meta small text-muted mb-2">
                @if($teaserDate)
                    {{ $teaserDate instanceof \Illuminate\Support\Carbon ? $teaserDate->format('d.m.Y') : $teaserDate }}
                @endif
                <span class="mx-1">|</span>
                {{ $teaserReadingMinutes }} min czytania
            </div>

            <h2 class="blog-teaser__title h3 fw-bold mb-3">
                @if($teaserIsExample)
                    {{ $teaserTitle }}
                @else
                    <a href="{{ route('blog.show', $teaserSlug) }}" class="text-dark text-decoration-none">
                        {{ $teaserTitle }}
                    </a>
                @endif
            </h2>

            <p class="blog-teaser__excerpt text-muted mb-4 mb-lg-5">
                {{ $teaserExcerpt }}
            </p>

            @unless($teaserIsExample)
                <a href="{{ route('blog.show', $teaserSlug) }}" class="btn btn-outline-primary">
                    Czytaj artykuł
                </a>
            @else
                <span class="btn btn-outline-secondary disabled" aria-disabled="true">
                    Czytaj artykuł
                </span>
            @endunless
        </div>
    </div>
</article>
