@extends('layouts.app')

@section('title', $article->seoTitle().' - Platforma Nowoczesnej Edukacji')
@section('meta_description', $article->seoDescription())
@section('canonical', route('blog.show', $article->slug))
@section('og_type', 'article')
@section('og_image', $article->publicImageUrl() ?? config('seo.default_og_image'))
@section('og_image_alt', $article->plainTitle())
@section('article_meta')
    @if($article->published_at)
        <meta property="article:published_time" content="{{ $article->published_at->toAtomString() }}">
    @endif
    @if($article->updated_at)
        <meta property="article:modified_time" content="{{ $article->updated_at->toAtomString() }}">
    @endif
@endsection

@push('structured-data')
@php
    $baseUrl = rtrim((string) config('app.url'), '/');
    $articleUrl = route('blog.show', $article->slug);
    $articleImage = $article->publicImageUrl() ?? config('seo.default_og_image');
    $articleSchema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'BlogPosting',
                '@id' => $articleUrl.'#article',
                'mainEntityOfPage' => [
                    '@type' => 'WebPage',
                    '@id' => $articleUrl,
                ],
                'headline' => $article->plainTitle(),
                'description' => $article->seoDescription(),
                'image' => array_values(array_filter([$articleImage])),
                'datePublished' => $article->published_at?->toAtomString(),
                'dateModified' => $article->updated_at?->toAtomString() ?? $article->published_at?->toAtomString(),
                'author' => [
                    '@type' => 'Organization',
                    '@id' => $baseUrl.'/#organization',
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    '@id' => $baseUrl.'/#organization',
                ],
                'inLanguage' => 'pl-PL',
                'isPartOf' => [
                    '@type' => 'Blog',
                    '@id' => route('blog.index').'#blog',
                    'name' => 'Blog Platformy Nowoczesnej Edukacji',
                ],
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => $articleUrl.'#breadcrumb',
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
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $article->plainTitle(),
                        'item' => $articleUrl,
                    ],
                ],
            ],
        ],
    ];
@endphp
<script type="application/ld+json">
{!! json_encode($articleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@push('styles')
<style>
    .blog-article__cover {
        aspect-ratio: 16 / 9;
        object-fit: cover;
        display: block;
    }

    .article-content {
        font-size: 1rem;
        line-height: 1.75;
        color: #212529;
        min-width: 0;
        max-width: 100%;
        overflow-wrap: break-word;
    }

    .blog-article .row > [class*="col-"] {
        min-width: 0;
    }

    .article-content > :first-child {
        margin-top: 0;
    }

    .article-content > :last-child {
        margin-bottom: 0;
    }

    .article-content p,
    .article-content ul,
    .article-content ol,
    .article-content blockquote,
    .article-content pre,
    .article-content figure {
        margin-bottom: 1.25rem;
    }

    .article-content h2,
    .article-content h3,
    .article-content h4 {
        margin-top: 2rem;
        margin-bottom: 1rem;
        line-height: 1.3;
    }

    .article-content img {
        max-width: 100%;
        height: auto;
        border-radius: 0.5rem;
    }

    .article-content blockquote {
        border-left: 4px solid #0d6efd;
        padding-left: 1rem;
        color: #495057;
    }

    .article-content a {
        word-break: break-word;
    }

    .article-content table {
        width: 100% !important;
        max-width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        margin-bottom: 1.25rem;
    }

    .article-content th,
    .article-content td {
        padding: 0.65rem 0.75rem;
        border: 1px solid #dee2e6;
        vertical-align: top;
        white-space: normal !important;
        overflow-wrap: anywhere;
        word-break: break-word;
        hyphens: auto;
    }

    .article-content th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .article-content pre {
        max-width: 100%;
        overflow-x: auto;
        white-space: pre-wrap;
        word-break: break-word;
    }

    @media (max-width: 767.98px) {
        .article-content table {
            font-size: 0.9375rem;
        }

        .article-content th,
        .article-content td {
            padding: 0.5rem 0.55rem;
        }
    }

    @media (min-width: 768px) {
        .article-content {
            font-size: 1.0625rem;
        }
    }

    @media (min-width: 992px) {
        .article-content {
            font-size: 1.125rem;
            line-height: 1.8;
        }
    }

    .blog-article-share {
        padding: 0.85rem 0.75rem 0.75rem;
        border: 1px solid #cfe2ff;
        border-radius: 0.5rem;
        background: linear-gradient(180deg, #eef4ff 0%, #e8f1ff 100%);
        text-align: center;
    }

    .blog-article-share__label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #084298;
        margin-bottom: 0.6rem;
    }

    .blog-article-share__icons {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.5rem;
    }

    .blog-article-share__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        border: 1px solid #dee2e6;
        background: #fff;
        color: #495057;
        text-decoration: none;
        transition: color 0.15s ease, background-color 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
    }

    .blog-article-share__btn:hover,
    .blog-article-share__btn:focus-visible {
        color: #fff;
        transform: translateY(-1px);
    }

    .blog-article-share__btn--facebook:hover,
    .blog-article-share__btn--facebook:focus-visible {
        background: #1877f2;
        border-color: #1877f2;
    }

    .blog-article-share__btn--linkedin:hover,
    .blog-article-share__btn--linkedin:focus-visible {
        background: #0a66c2;
        border-color: #0a66c2;
    }

    .blog-article-share__btn--x:hover,
    .blog-article-share__btn--x:focus-visible {
        background: #000;
        border-color: #000;
    }

    .blog-article-share__btn--email:hover,
    .blog-article-share__btn--email:focus-visible {
        background: #0d6efd;
        border-color: #0d6efd;
    }

    .blog-article-share__btn--copy:hover,
    .blog-article-share__btn--copy:focus-visible,
    .blog-article-share__btn--copy.is-copied {
        background: #198754;
        border-color: #198754;
        color: #fff;
    }

    .blog-article-share__btn i {
        font-size: 1.15rem;
        line-height: 1;
    }

    .blog-article-share__feedback {
        min-height: 1rem;
        margin-top: 0.45rem;
        font-size: 0.75rem;
        color: #146c43;
    }
</style>
@endpush

@section('content')
@php
    $shareUrl = route('blog.show', $article->slug);
    $shareTitle = $article->plainTitle();
@endphp
<article class="blog-article">
    <section class="bg-primary bg-gradient text-white py-4 py-md-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-md-11 col-lg-8 col-xl-7">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-3">
                            <li class="breadcrumb-item"><a class="text-white" href="{{ route('home') }}">Start</a></li>
                            <li class="breadcrumb-item"><a class="text-white" href="{{ route('blog.index') }}">Blog</a></li>
                            <li class="breadcrumb-item active text-white-50" aria-current="page">{{ $article->plainTitle() }}</li>
                        </ol>
                    </nav>

                    <h1 class="display-6 fw-bold mb-3">{{ $article->plainTitle() }}</h1>
                    @if(filled($article->excerpt))
                        <p class="lead mb-3">{{ $article->plainExcerpt() }}</p>
                    @endif
                    <div class="text-white-50">
                        {{ $article->published_at?->format('d.m.Y') }}
                        <span class="mx-1">|</span>
                        {{ $article->readingTimeMinutes() }} min czytania
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-4 py-md-5">
        <div class="container">
            @if($article->publicImageUrl())
                <div class="row justify-content-center mb-4 mb-lg-5">
                    <div class="col-12 col-md-11 col-lg-10 col-xl-9">
                        <img src="{{ $article->publicImageUrl() }}"
                             alt="{{ $article->plainTitle() }}"
                             class="blog-article__cover img-fluid rounded-3 shadow-sm w-100"
                             loading="lazy">
                    </div>
                </div>
            @endif

            <div class="row justify-content-center">
                <div class="col-12 col-md-11 col-lg-8 col-xl-7">
                    <div class="article-content">
                        {!! $article->content_html !!}
                    </div>

                    <hr class="my-4 my-lg-5">

                    <div class="blog-article-share mb-3" aria-label="Udostępnij artykuł">
                        <div class="blog-article-share__label">Udostępnij artykuł</div>
                        <div class="blog-article-share__icons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}"
                               class="blog-article-share__btn blog-article-share__btn--facebook"
                               target="_blank"
                               rel="noopener noreferrer"
                               aria-label="Udostępnij na Facebooku">
                                <i class="bi bi-facebook" aria-hidden="true"></i>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}"
                               class="blog-article-share__btn blog-article-share__btn--linkedin"
                               target="_blank"
                               rel="noopener noreferrer"
                               aria-label="Udostępnij na LinkedIn">
                                <i class="bi bi-linkedin" aria-hidden="true"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($shareTitle) }}"
                               class="blog-article-share__btn blog-article-share__btn--x"
                               target="_blank"
                               rel="noopener noreferrer"
                               aria-label="Udostępnij na X">
                                <i class="bi bi-twitter-x" aria-hidden="true"></i>
                            </a>
                            <a href="mailto:?subject={{ rawurlencode($shareTitle) }}&body={{ rawurlencode($shareUrl) }}"
                               class="blog-article-share__btn blog-article-share__btn--email"
                               aria-label="Wyślij artykuł e-mailem">
                                <i class="bi bi-envelope" aria-hidden="true"></i>
                            </a>
                            <button type="button"
                                    class="blog-article-share__btn blog-article-share__btn--copy"
                                    id="blog-article-copy-link"
                                    data-share-url="{{ $shareUrl }}"
                                    aria-label="Kopiuj link do artykułu">
                                <i class="bi bi-link-45deg" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="blog-article-share__feedback" id="blog-article-share-feedback" aria-live="polite"></div>
                    </div>

                    <div class="mb-4">
                        <a href="{{ route('blog.index') }}" class="btn btn-outline-primary">
                            Wróć do bloga
                        </a>
                    </div>

                    @if($article->comments_enabled)
                        <div class="alert alert-light border mt-4 mt-lg-5 mb-0">
                            <h2 class="h5 mb-2">Komentarze</h2>
                            <p class="mb-0">
                                Komentarze są przewidziane jako następny etap. Formularz publiczny zostanie dodany razem z moderacją i ochroną antyspamową.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</article>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.pneMarkBlogSeen === 'function') {
        window.pneMarkBlogSeen(@json($seenAt));
    }

    var copyBtn = document.getElementById('blog-article-copy-link');
    var feedback = document.getElementById('blog-article-share-feedback');
    if (!copyBtn || !feedback) {
        return;
    }

    copyBtn.addEventListener('click', async function () {
        var url = copyBtn.getAttribute('data-share-url') || window.location.href;

        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(url);
            } else {
                var helper = document.createElement('textarea');
                helper.value = url;
                helper.setAttribute('readonly', 'readonly');
                helper.style.position = 'fixed';
                helper.style.left = '-9999px';
                document.body.appendChild(helper);
                helper.select();
                document.execCommand('copy');
                document.body.removeChild(helper);
            }

            copyBtn.classList.add('is-copied');
            feedback.textContent = 'Link skopiowany do schowka.';
            clearTimeout(copyBtn._copiedTimer);
            copyBtn._copiedTimer = setTimeout(function () {
                copyBtn.classList.remove('is-copied');
                feedback.textContent = '';
            }, 2500);
        } catch (error) {
            feedback.textContent = 'Nie udało się skopiować linku.';
        }
    });
});
</script>
@endpush
@endsection
