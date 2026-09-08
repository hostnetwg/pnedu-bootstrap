@extends('layouts.app')

@section('title', 'Odstąpienie od umowy – pnedu.pl')
@section('meta_description', 'Informacja o prawie odstąpienia od umowy zawartej na odległość oraz wzór formularza.')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="mb-0">Odstąpienie od umowy</h1>
            </div>
            <div class="card-body">
                <p>Konsument może co do zasady odstąpić od umowy zawartej na odległość w terminie 14 dni od jej zawarcia. Oświadczenie można przesłać na adres <a href="mailto:kontakt@pnedu.pl">kontakt@pnedu.pl</a> lub pocztą na adres PNE.</p>
                <p>Jeżeli na wyraźne żądanie konsumenta szkolenie rozpoczęło się przed upływem tego terminu, rozliczenie świadczenia wykonanego do chwili odstąpienia następuje zgodnie z ustawą i Regulaminem. Po pełnym wykonaniu usługi lub prawidłowo rozpoczętym dostarczaniu treści cyfrowej prawo może wygasnąć na zasadach opisanych w Regulaminie.</p>
                <p><a class="btn btn-outline-primary" href="{{ route('withdrawal.pdf') }}">Pobierz wzór formularza PDF</a></p>

                <h2 class="h4 mt-4">Wzór oświadczenia</h2>
                @include('legal.withdrawal-form-content')
            </div>
        </div>
    </div>
</section>
@endsection
