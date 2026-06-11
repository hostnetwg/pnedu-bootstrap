<script>
(function () {
    function initSzkoleniaListAjax() {
        var root = document.querySelector('.js-szkolenia-list-root');
        if (!root) {
            return;
        }

        var fragmentBaseUrl = root.getAttribute('data-fragment-url');
        var pageBaseUrl = root.getAttribute('data-page-url');
        if (!fragmentBaseUrl || !pageBaseUrl) {
            return;
        }

        var activeRequest = null;

        function applyTypParam(target, typ) {
            if (!typ || typ === 'all') {
                target.searchParams.delete('typ');
            } else {
                target.searchParams.set('typ', typ);
            }
        }

        function buildFragmentUrl(pageUrl) {
            var source = new URL(pageUrl, window.location.origin);
            var target = new URL(fragmentBaseUrl, window.location.origin);
            applyTypParam(target, source.searchParams.get('typ') || 'all');

            var page = source.searchParams.get('page');
            if (page) {
                target.searchParams.set('page', page);
            } else {
                target.searchParams.delete('page');
            }

            return target.toString();
        }

        function buildPageUrl(pageUrl) {
            var source = new URL(pageUrl, window.location.origin);
            var target = new URL(pageBaseUrl, window.location.origin);
            applyTypParam(target, source.searchParams.get('typ') || 'all');

            var page = source.searchParams.get('page');
            if (page && page !== '1') {
                target.searchParams.set('page', page);
            } else {
                target.searchParams.delete('page');
            }

            return target.pathname + target.search + target.hash;
        }

        function setLoading(loading) {
            root.setAttribute('aria-busy', loading ? 'true' : 'false');
            root.classList.toggle('szkolenia-list-root--loading', loading);
        }

        function loadList(sourceUrl, pushState) {
            if (activeRequest) {
                activeRequest.abort();
            }

            var fragmentUrl = buildFragmentUrl(sourceUrl);
            var controller = new AbortController();
            activeRequest = controller;

            setLoading(true);

            return fetch(fragmentUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
                credentials: 'same-origin',
                signal: controller.signal,
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Szkolenia list fragment request failed');
                    }

                    return response.text();
                })
                .then(function (html) {
                    if (activeRequest !== controller) {
                        return;
                    }

                    root.innerHTML = html;
                    setLoading(false);
                    activeRequest = null;

                    if (pushState) {
                        history.pushState({ szkoleniaList: true }, '', buildPageUrl(sourceUrl));
                    }
                })
                .catch(function (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    setLoading(false);
                    activeRequest = null;
                });
        }

        root.addEventListener('click', function (event) {
            var link = event.target.closest('a');
            if (!link || !root.contains(link)) {
                return;
            }

            var isFilter = link.closest('.js-szkolenia-list-filters');
            var isPagination = link.closest('.js-szkolenia-list-pagination');
            if (!isFilter && !isPagination) {
                return;
            }

            event.preventDefault();
            loadList(link.href, true);
        });

        window.addEventListener('popstate', function () {
            if (!window.location.pathname.endsWith('/dashboard/szkolenia')) {
                return;
            }

            loadList(window.location.href, false);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSzkoleniaListAjax);
    } else {
        initSzkoleniaListAjax();
    }
})();
</script>
