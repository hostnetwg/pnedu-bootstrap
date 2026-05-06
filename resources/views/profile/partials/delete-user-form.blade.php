<div class="card border-danger">
    <div class="card-header text-bg-danger">{{ __('Usuń konto') }}</div>

    <div class="card-body">
        <div class="alert alert-warning mb-3" role="alert">
            <strong>Uwaga.</strong>
            Ta czynność trwale blokuje logowanie na to konto. Profil jest dezaktywowany (usuwanie nieodwracalne z poziomu strony —
            rekord pozostaje w systemie dla celów księgowych / prawnych). Ten sam adres e-mail możesz ponownie wykorzystać przy nowej rejestracji,
            dopóki ktoś inny go nie zajmie.
        </div>

        <div class="mb-3 text-muted">
            Jeśli chcesz zachować coś ze swojego konta (np. dane zamówień lub certyfikatów), zrób to przed usunięciem.
        </div>

        <div class="row mb-0">
            <div class="col-md-6">
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                    {{ __('Usuń konto') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="deleteAccountModalLabel">
            {{ __('Czy na pewno chcesz usunąć swoje konto?') }}
        </h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger mb-3" role="alert">
            Zamierzasz <strong>usunąć konto</strong>. Nie będziesz mógł/mogła się na nie zalogować. Konto zostaje oznaczone jako usunięte
            (soft delete — wpis może pozostać w bazie zgodnie z przechowywaniem danych). Przywrócenia konta z tej strony nie ma.
        </div>
        <p class="mb-3">
            Wpisz swoje aktualne hasło, żeby potwierdzić, że to Ty.
        </p>
        <form id="deleteAccountForm" method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <div>
                <input type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" name="password" placeholder="{{ __('Hasło') }}" required>

                @error('password', 'userDeletion')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            {{ __('Anuluj') }}
        </button>
        <button type="submit" class="btn btn-danger" form="deleteAccountForm">
            {{ __('Usuń konto') }}
        </button>
      </div>
    </div>
  </div>
</div>

@push('scripts')
    @php $shouldOpenModal = $errors->userDeletion->isNotEmpty(); @endphp

    <script>
        let shouldOpenModal = {{ Js::from($shouldOpenModal) }};

        if (shouldOpenModal) {
            window.addEventListener('load', function() {
                let deleteAccountModal = new bootstrap.Modal('#deleteAccountModal');
                deleteAccountModal.toggle();
            });
        }
    </script>
@endPush