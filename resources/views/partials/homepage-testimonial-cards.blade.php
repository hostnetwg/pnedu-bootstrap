@foreach($testimonials as $testimonial)
    <div class="col-md-5" data-testimonial-id="{{ $testimonial->id }}">
        <div class="card border-0 shadow h-100 p-4">
            <div class="d-flex mb-4">
                @php $stars = max(1, min(5, (int) ($testimonial->rating ?? 5))); @endphp
                @for($i = 1; $i <= 5; $i++)
                    <i class="bi bi-star{{ $i <= $stars ? '-fill' : '' }} text-warning{{ $i < 5 ? ' me-1' : '' }}"></i>
                @endfor
            </div>
            <p class="fs-5 mb-4">„{{ $testimonial->quote }}”</p>
            <div class="d-flex align-items-center mt-auto">
                @if($testimonial->hasAvatar())
                    <img src="{{ $testimonial->avatarUrl() }}" alt="{{ $testimonial->author_name }}"
                         class="rounded-circle me-3" width="70" height="70" style="object-fit:cover;background:#eef2f6;">
                @else
                    <div class="rounded-circle me-3 d-flex align-items-center justify-content-center text-white fw-bold"
                         style="width:70px;height:70px;background:#6c757d;font-size:.95rem;"
                         aria-hidden="true">{{ $testimonial->initials() }}</div>
                @endif
                <div>
                    <h6 class="fw-bold mb-1">{{ $testimonial->author_name }}</h6>
                    @if($testimonial->subtitle() !== '')
                        <small class="text-muted">{{ $testimonial->subtitle() }}</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach
