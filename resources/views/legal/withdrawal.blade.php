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
                <p>Konsument oraz przedsiębiorca objęty ochroną konsumencką (w tym część JDG przy zakupie niezawodowym) może co do zasady odstąpić od umowy zawartej na odległość w terminie 14 dni od jej zawarcia, bez podania przyczyny. Termin liczy się od dnia zawarcia umowy, także przy przedsprzedaży kursu. Oświadczenie można przesłać na adres <a href="mailto:kontakt@pnedu.pl">kontakt@pnedu.pl</a> lub pocztą na adres PNE. Wzór poniżej nie jest obowiązkowy.</p>
                <p>To prawo dotyczy szkoleń, kursów nagranych, treści cyfrowych, usług cyfrowych oraz przedłużenia dostępu — każde jako osobna umowa, której dotyczy dane zamówienie.</p>
                <p>Jeżeli na wyraźne żądanie konsumenta usługa (w tym usługa cyfrowa) rozpoczęła się przed upływem 14 dni, a konsument odstąpi przed jej pełnym wykonaniem, rozliczenie świadczenia wykonanego do chwili odstąpienia następuje zgodnie z ustawą i Regulaminem. Uruchomienie dostępu na 12 lub 24 miesiące nie oznacza samo w sobie pełnego wykonania usługi.</p>
                <p>W odniesieniu do odpłatnych treści cyfrowych niedostarczanych na nośniku materialnym prawo odstąpienia może wygasnąć z chwilą rozpoczęcia ich dostarczania, jeżeli konsument złożył wyraźną uprzednią zgodę, przyjął do wiadomości skutek i otrzymał wymagane potwierdzenie na trwałym nośniku. Samo złożenie zamówienia, zapłata lub utworzenie konta nie jest taką zgodą.</p>
                <p>Dodatkowa gwarancja satysfakcji, jeżeli została przyobiecana przy zakupie, nie zastępuje i nie ogranicza ustawowego odstąpienia. Ograniczenie zgłoszeń gwarancji do e-maila i telefonu nie dotyczy tego ustawowego prawa.</p>
                <p>Przy ustawowym odstąpieniu PNE zwraca zapłatę zgodnie z przepisami. Serwis nie uruchamia automatycznej wypłaty z panelu użytkownika.</p>
                <p><a class="btn btn-outline-primary" href="{{ route('withdrawal.pdf') }}">Pobierz wzór formularza PDF</a></p>

                <h2 class="h4 mt-4">Wzór oświadczenia</h2>
                @include('legal.withdrawal-form-content')
            </div>
        </div>
    </div>
</section>
@endsection
