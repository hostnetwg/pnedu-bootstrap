@extends('layouts.app')

@section('title', 'Blog - Platforma Nowoczesnej Edukacji')
@section('meta_description', 'Artykuły, porady i praktyczne inspiracje dla nauczycieli, dyrektorów oraz osób rozwijających nowoczesną edukację.')
@section('canonical', $search === '' && $articles->currentPage() > 1 ? $articles->url($articles->currentPage()) : route('blog.index'))
@if($search !== '')
    @section('robots', 'noindex, follow')
@endif
@section('pagination_links')
    @if($search === '')
        @if($articles->previousPageUrl())
            <link rel="prev" href="{{ $articles->previousPageUrl() }}">
        @endif
        @if($articles->nextPageUrl())
            <link rel="next" href="{{ $articles->nextPageUrl() }}">
        @endif
    @endif
@endsection

@push('structured-data')
@php
    $baseUrl = rtrim((string) config('app.url'), '/');
    $startPosition = (($articles->currentPage() - 1) * $articles->perPage()) + 1;
    $blogItems = $articles->getCollection()->values()->map(function ($article, int $index) use ($startPosition) {
        return [
            '@type' => 'ListItem',
            'position' => $startPosition + $index,
            'url' => route('blog.show', $article->slug),
            'name' => $article->plainTitle(),
        ];
    })->all();

    $blogSchema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Blog',
                '@id' => route('blog.index').'#blog',
                'name' => 'Blog Platformy Nowoczesnej Edukacji',
                'description' => 'Artykuły, porady i praktyczne inspiracje dla nauczycieli, dyrektorów oraz osób rozwijających nowoczesną edukację.',
                'url' => route('blog.index'),
                'inLanguage' => 'pl-PL',
                'publisher' => ['@id' => $baseUrl.'/#organization'],
            ],
            [
                '@type' => 'ItemList',
                '@id' => url()->current().'#blog-list',
                'name' => 'Lista artykułów bloga Platformy Nowoczesnej Edukacji',
                'itemListElement' => $blogItems,
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => route('blog.index').'#breadcrumb',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Strona główna',
                        'item' => route('home'),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'Blog',
                        'item' => route('blog.index'),
                    ],
                ],
            ],
        ],
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($blogSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@push('styles')
<style>
    .blog-teaser {
        width: 100%;
        padding: 2.5rem 0;
        border-bottom: 1px solid #dee2e6;
    }

    .blog-teaser:first-of-type {
        padding-top: 0;
    }

    .blog-teaser:last-of-type {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .blog-teaser__image {
        aspect-ratio: 16 / 9;
        object-fit: cover;
        display: block;
    }

    .blog-teaser__image--placeholder {
        min-height: 220px;
        background: linear-gradient(135deg, #0d6efd 0%, #084298 100%);
    }

    .blog-teaser__title a:hover {
        color: #0d6efd !important;
    }

    @media (min-width: 992px) {
        .blog-teaser__excerpt {
            font-size: 1.125rem;
            line-height: 1.7;
        }
    }
</style>
@endpush

@section('content')

@section('main-padding', '')

<div class="bg-primary bg-gradient text-white py-5 text-center">
    <div class="container">
        <h1 class="display-5 fw-bold mb-3">Blog Platformy Nowoczesnej Edukacji</h1>
        <p class="lead fw-semibold mb-0">Artykuły, porady i nowości ze świata edukacji</p>
    </div>
</div>

<div class="container-fluid px-3 px-md-4 px-xl-5 py-5">
    <div class="row justify-content-center mb-5">
        <div class="col-12 col-xl-10">
            <form method="GET" action="{{ route('blog.index') }}" class="input-group input-group-lg">
                <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Szukaj artykułów">
                <button type="submit" class="btn btn-primary">Szukaj</button>
                @if($search !== '')
                    <a href="{{ route('blog.index') }}" class="btn btn-outline-secondary">Wyczyść</a>
                @endif
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @if($articles->count() > 0)
                @foreach($articles as $article)
                    @include('blog.partials.teaser', [
                        'title' => $article->plainTitle(),
                        'slug' => $article->slug,
                        'excerpt' => $article->plainExcerpt(),
                        'publishedAt' => $article->published_at,
                        'readingMinutes' => $article->readingTimeMinutes(),
                        'imageUrl' => $article->publicImageUrl(),
                    ])
                @endforeach

                @if($articles->hasPages())
                    <div class="mt-5 pt-3">
                        {{ $articles->links() }}
                    </div>
                @endif
            @elseif($search !== '')
                <div class="text-center py-5">
                    <i class="bi bi-journal-text display-3 text-muted"></i>
                    <h2 class="h4 mt-3">Brak wyników wyszukiwania</h2>
                    <p class="text-muted mb-0">Nie znaleziono artykułów dla podanej frazy.</p>
                </div>
            @else
                @include('blog.partials.teaser', [
                    'isExample' => true,
                    'showExampleNotice' => true,
                    'title' => 'Sztuczna inteligencja w edukacji: możliwości i zagrożenia',
                    'excerpt' => 'Sztuczna inteligencja coraz mocniej wspiera nauczanie, ocenianie i organizację pracy szkoły. Zobacz, jak wykorzystać ją praktycznie — i na co uważać, planując wdrożenia w placówce oświatowej.',
                    'publishedAt' => '08.07.2025',
                    'readingMinutes' => 4,
                    'imageUrl' => 'https://placehold.co/1200x675/0d6efd/ffffff?text=Sztuczna+Inteligencja+w+Edukacji',
                ])

                @include('blog.partials.teaser', [
                    'isExample' => true,
                    'title' => 'Wykorzystanie aplikacji Canva w pracy nauczyciela',
                    'excerpt' => 'Canva pozwala szybko tworzyć estetyczne materiały dydaktyczne: prezentacje, plakaty, certyfikaty i infografiki. Sprawdź, jak wpleść to narzędzie w codzienną pracę nauczyciela bez tracenia czasu na skomplikowaną grafikę.',
                    'publishedAt' => '10.07.2025',
                    'readingMinutes' => 3,
                    'imageUrl' => 'https://placehold.co/1200x675/084298/ffffff?text=Wykorzystanie+aplikacji+Canva',
                ])

                <div class="text-center text-muted small mt-4">
                    Po opublikowaniu artykułów w panelu administracyjnym powyższe przykłady zostaną zastąpione rzeczywistymi wpisami.
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.pneMarkBlogSeen === 'function') {
        window.pneMarkBlogSeen(@json($blogSeenAt));
    }
});
</script>
@endpush

@endsection
