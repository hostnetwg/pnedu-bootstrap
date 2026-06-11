/**
 * Asynchroniczne ładowanie sekcji „Aktualna oferta” w panelu użytkownika.
 * Jeden request wypełnia wszystkie mountpointy (desktop + mobile).
 */
document.addEventListener('DOMContentLoaded', () => {
    const containers = document.querySelectorAll('.js-dashboard-offer-sidebar[data-offer-url]');
    if (!containers.length) {
        return;
    }

    const url = containers[0].dataset.offerUrl;
    if (!url) {
        return;
    }

    let offerHtmlPromise = null;

    const fetchOfferHtml = () => {
        offerHtmlPromise ??= fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/html',
            },
            credentials: 'same-origin',
        }).then((response) => {
            if (!response.ok) {
                throw new Error('Offer fragment request failed');
            }

            return response.text();
        });

        return offerHtmlPromise;
    };

    const markLoaded = (element) => {
        element.classList.add('dashboard-offer-sidebar-mount--loaded');
        element.setAttribute('aria-busy', 'false');
    };

    const showError = () => {
        const message = `
            <div class="dashboard-sidebar-offer">
                <p class="dashboard-sidebar-upcoming__empty mb-0">
                    Nie udało się załadować oferty.
                    <button type="button" class="btn btn-link btn-sm p-0 align-baseline js-dashboard-offer-retry">
                        Spróbuj ponownie
                    </button>
                </p>
            </div>`;

        containers.forEach((element) => {
            element.innerHTML = message;
            element.setAttribute('aria-busy', 'false');
            element.querySelector('.js-dashboard-offer-retry')?.addEventListener('click', () => {
                offerHtmlPromise = null;
                containers.forEach((container) => {
                    container.setAttribute('aria-busy', 'true');
                    container.classList.remove('dashboard-offer-sidebar-mount--loaded');
                });
                loadOfferSidebars();
            });
        });
    };

    const loadOfferSidebars = () => {
        fetchOfferHtml()
            .then((html) => {
                containers.forEach((element) => {
                    element.innerHTML = html;
                    markLoaded(element);
                });
            })
            .catch(showError);
    };

    loadOfferSidebars();
});
