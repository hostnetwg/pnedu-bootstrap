<script>
(function () {
    function initDashboardOfferSidebars() {
        var containers = document.querySelectorAll('.js-dashboard-offer-sidebar[data-offer-url]');
        if (!containers.length) {
            return;
        }

        var url = containers[0].getAttribute('data-offer-url');
        if (!url) {
            return;
        }

        var offerHtmlPromise = null;

        function fetchOfferHtml() {
            if (!offerHtmlPromise) {
                offerHtmlPromise = fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'text/html',
                    },
                    credentials: 'same-origin',
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error('Offer fragment request failed');
                    }

                    return response.text();
                });
            }

            return offerHtmlPromise;
        }

        function markLoaded(element) {
            element.classList.add('dashboard-offer-sidebar-mount--loaded');
            element.setAttribute('aria-busy', 'false');
        }

        function showError() {
            var message = '<div class="dashboard-sidebar-offer"><p class="dashboard-sidebar-upcoming__empty mb-0">Nie udało się załadować oferty. <button type="button" class="btn btn-link btn-sm p-0 align-baseline js-dashboard-offer-retry">Spróbuj ponownie</button></p></div>';

            containers.forEach(function (element) {
                element.innerHTML = message;
                element.setAttribute('aria-busy', 'false');

                var retryButton = element.querySelector('.js-dashboard-offer-retry');
                if (retryButton) {
                    retryButton.addEventListener('click', function () {
                        offerHtmlPromise = null;
                        containers.forEach(function (container) {
                            container.setAttribute('aria-busy', 'true');
                            container.classList.remove('dashboard-offer-sidebar-mount--loaded');
                        });
                        loadOfferSidebars();
                    });
                }
            });
        }

        function loadOfferSidebars() {
            fetchOfferHtml()
                .then(function (html) {
                    containers.forEach(function (element) {
                        element.innerHTML = html;
                        markLoaded(element);
                    });
                })
                .catch(showError);
        }

        loadOfferSidebars();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDashboardOfferSidebars);
    } else {
        initDashboardOfferSidebars();
    }
})();
</script>
