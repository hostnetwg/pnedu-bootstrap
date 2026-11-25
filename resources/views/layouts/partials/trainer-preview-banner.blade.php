@if($trainerPreviewInstructor ?? null)
    <div class="alert alert-warning rounded-0 mb-0 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-2">
        <span>Podgląd panelu trenera: <strong>{{ $trainerPreviewInstructor->full_name }}</strong>. Zapis terminu działa tak, jakby zrobił to ten trener.</span>
        <form method="POST" action="{{ route('trainer.preview.stop') }}" class="mb-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-dark">Wróć do swojego konta</button>
        </form>
    </div>
@endif
