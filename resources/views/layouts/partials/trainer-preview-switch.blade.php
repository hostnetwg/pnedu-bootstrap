@if(($trainerSwitchInstructors ?? collect())->isNotEmpty())
    <li><h6 class="dropdown-header">Panele trenerów</h6></li>
    @foreach($trainerSwitchInstructors as $switchInstructor)
        <li>
            <form method="POST" action="{{ route('trainer.preview.start', $switchInstructor) }}">
                @csrf
                <button type="submit" class="dropdown-item @if(($trainerPreviewInstructor->id ?? null) === $switchInstructor->id) active @endif">
                    {{ $switchInstructor->full_name_with_title }}
                </button>
            </form>
        </li>
    @endforeach
    @if($trainerPreviewInstructor ?? null)
        <li>
            <form method="POST" action="{{ route('trainer.preview.stop') }}">
                @csrf
                <button type="submit" class="dropdown-item">Wróć do swojego konta</button>
            </form>
        </li>
    @endif
@endif
