{{-- Licznik nowych artykułów przy menu Blog (localStorage per przeglądarka). --}}
<style>
    .blog-nav-link {
        position: relative;
    }
    .blog-nav-badge {
        position: absolute;
        top: 0.1rem;
        right: -0.15rem;
        min-width: 1.15rem;
        height: 1.15rem;
        padding: 0 0.3rem;
        border-radius: 999px;
        background: #dc3545;
        color: #fff;
        font-size: 0.625rem;
        font-weight: 700;
        line-height: 1.15rem;
        text-align: center;
        box-shadow: 0 0 0 2px #f8f9fa;
    }
    @media (max-width: 991.98px) {
        .blog-nav-badge {
            top: 0.35rem;
            right: 0.35rem;
        }
    }
</style>
<script>
(function () {
    var STORAGE_KEY = 'pne_blog_last_seen_at';

    function readSeenAt() {
        try {
            return localStorage.getItem(STORAGE_KEY) || '';
        } catch (error) {
            return '';
        }
    }

    window.pneMarkBlogSeen = function (isoTimestamp) {
        var next = isoTimestamp || new Date().toISOString();
        var current = readSeenAt();

        if (!current || next > current) {
            try {
                localStorage.setItem(STORAGE_KEY, next);
            } catch (error) {
                // localStorage niedostępny — pomijamy
            }
        }

        window.pneUpdateBlogNavBadge();
    };

    window.pneUpdateBlogNavBadge = async function () {
        var badge = document.getElementById('blog-nav-badge');
        if (!badge) {
            return;
        }

        var url = @json(route('blog.new-count'));
        var since = readSeenAt();
        if (since) {
            url += (url.indexOf('?') >= 0 ? '&' : '?') + 'since=' + encodeURIComponent(since);
        }

        try {
            var response = await fetch(url, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                badge.classList.add('d-none');
                return;
            }

            var data = await response.json();
            var count = parseInt(data.count, 10) || 0;

            if (count > 0) {
                badge.textContent = count > 9 ? '9+' : String(count);
                badge.classList.remove('d-none');
                badge.setAttribute('aria-label', count + ' nowych artykułów na blogu');
            } else {
                badge.classList.add('d-none');
                badge.removeAttribute('aria-label');
            }
        } catch (error) {
            badge.classList.add('d-none');
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.pneUpdateBlogNavBadge();
    });

    window.addEventListener('storage', function (event) {
        if (event.key === STORAGE_KEY) {
            window.pneUpdateBlogNavBadge();
        }
    });
})();
</script>
