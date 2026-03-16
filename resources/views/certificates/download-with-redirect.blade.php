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
                        Za chwilę zostaniesz przekierowany na stronę główną.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<iframe id="certificate-download-frame" style="position:absolute;width:0;height:0;border:0;" title="Pobieranie pliku"></iframe>

<script>
(function() {
    var downloadUrl = {!! json_encode($downloadUrl) !!};
    var homeUrl = {!! json_encode($homeUrl) !!};
    var frame = document.getElementById('certificate-download-frame');
    if (frame && downloadUrl) {
        frame.src = downloadUrl;
    }
    setTimeout(function() {
        window.location.href = homeUrl || '/';
    }, 2500);
})();
</script>
@endsection
