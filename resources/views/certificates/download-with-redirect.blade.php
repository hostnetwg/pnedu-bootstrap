@extends('layouts.app')

@section('title', 'Pobieranie zaświadczenia – ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4 text-center">
                    <div class="mb-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Ładowanie...</span>
                        </div>
                    </div>
                    <h2 class="h5 mb-2">Trwa pobieranie zaświadczenia</h2>
                    <p class="text-muted mb-0">
                        Za chwilę plik PDF z zaświadczeniem zostanie zapisany na Twoim komputerze. Następnie automatycznie przekierujemy Cię na stronę główną.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var downloadUrl = {!! json_encode($downloadUrl) !!};
    var homeUrl = {!! json_encode($homeUrl) !!};
    var redirected = false;
    var redirectDelayMs = 800;
    var maxWaitMs = 90000;

    function goHome() {
        if (redirected) return;
        redirected = true;
        var target = homeUrl || '/';
        var sep = (target.indexOf('?') === -1) ? '?' : '&';
        window.location.href = target + sep + 'certificate_downloaded=1';
    }

    if (!downloadUrl) {
        setTimeout(goHome, redirectDelayMs);
        return;
    }

    fetch(downloadUrl, { credentials: 'same-origin' })
        .then(function(r) {
            if (!r.ok) {
                goHome();
                return null;
            }
            var cd = r.headers.get('Content-Disposition');
            var filename = 'zaswiadczenie.pdf';
            if (cd) {
                var m = cd.match(/filename="?([^";]+)"?/);
                if (m) filename = m[1].trim();
            }
            return r.blob().then(function(blob) { return { blob: blob, filename: filename }; });
        })
        .then(function(data) {
            if (!data) return;
            var url = URL.createObjectURL(data.blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = data.filename;
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            setTimeout(goHome, redirectDelayMs);
        })
        .catch(function() {
            setTimeout(goHome, redirectDelayMs);
        });

    setTimeout(function() {
        if (!redirected) goHome();
    }, maxWaitMs);
})();
</script>
@endsection
