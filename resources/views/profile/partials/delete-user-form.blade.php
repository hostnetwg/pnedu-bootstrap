<div class="card">
    <div class="card-header">{{ __('Usuń konto') }}</div>

    <div class="card-body">
        <div class="mb-3">
            {{ __('Po usunięciu konta wszystkie jego zasoby i dane zostaną trwale usunięte. Przed usunięciem konta pobierz wszelkie dane albo informacje, które chcesz zachować.') }}
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
        <div class="mb-3">
            {{ __('Po usunięciu konta wszystkie jego zasoby i dane zostaną trwale usunięte. Wprowadź hasło, aby potwierdzić, że chcesz trwale usunąć swoje konto.') }}
        </div>
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