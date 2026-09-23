@extends('layouts.certificate-registration')

@section('title', 'Dostęp do nagrania – ' . config('app.name'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4 p-md-5 text-center">
                    <h1 class="h4 mb-3 text-primary">{{ !empty($accountCreated) ? 'Konto gotowe' : 'Dziękujemy' }}</h1>
                    @if(!empty($courseTitle))
                        <p class="fw-semibold mb-4">„{{ $courseTitle }}”</p>
                    @endif
                    @if(!empty($accountCreated))
                        <p class="mb-4">Jesteś na liście szkolenia. Nagranie i materiały są już w panelu — właśnie Cię zalogowaliśmy.</p>
                    @else
                        <p class="mb-4">Masz już konto na ten adres. Hasła nie zmieniliśmy. Zaloguj się dotychczasowym hasłem, żeby obejrzeć nagranie.</p>
                    @endif
                    @if(!empty($nextUrl))
                        <a href="{{ $nextUrl }}" class="btn btn-primary btn-lg px-4">
                            {{ !empty($accountCreated) ? 'Przejdź do nagrania' : 'Zaloguj się na pnedu.pl' }}
                        </a>
                        @if(!empty($accountCreated))
                            <p class="small text-muted mt-3 mb-0">
                                Po zalogowaniu, w panelu użytkownika, można zmienić hasło lub całkowicie usunąć konto na pnedu.pl.
                            </p>
                        @endif
                    @endif
                    @if(!empty($emailFailed))
                        <p class="small text-danger mt-3 mb-0">E-mail nie wyszedł. Użyj przycisku powyżej albo napisz na <a href="mailto:kontakt@pnedu.pl">kontakt@pnedu.pl</a>.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
