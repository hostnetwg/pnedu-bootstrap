{{-- 
    resources/views/layouts/cookie-consent.blade.php – baner zgody na używanie plików cookies
--}}

<div id="cookie-consent-banner" class="fixed-bottom bg-dark text-white p-3 d-none" style="z-index: 2000;">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center">
        <div class="mb-2 mb-md-0 opacity-75">
            Na naszej stronie używamy plików cookies w celu zapewnienia prawidłowego działania serwisu oraz analiz statystycznych. Szczegóły znajdziesz w <a href="{{ route('polityka-prywatnosci') }}" class="text-light text-decoration-underline">Polityce prywatności</a>.
        </div>
        <button id="accept-cookies" class="btn btn-primary btn-sm">Akceptuję</button>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var banner = document.getElementById('cookie-consent-banner');
    if (!localStorage.getItem('cookie_consent')) {
        banner.classList.remove('d-none');
        document.body.style.paddingBottom = banner.offsetHeight + 'px';
    }
    document.getElementById('accept-cookies').addEventListener('click', function() {
        localStorage.setItem('cookie_consent', 'accepted');
        banner.classList.add('d-none');
        document.body.style.paddingBottom = '';
    });
})();
</script>
@endpush