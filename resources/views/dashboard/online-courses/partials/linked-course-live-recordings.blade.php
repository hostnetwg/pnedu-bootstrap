@if(($linkedCourseLiveVideos ?? collect())->isNotEmpty())
    <section class="online-lesson-live-recordings mt-4 pt-4 border-top" aria-labelledby="lesson-live-recordings-heading">
        <div class="online-lesson-live-recordings-box border rounded-3 bg-body-secondary bg-opacity-50 p-3 p-md-4">
            <h2 id="lesson-live-recordings-heading" class="h6 mb-3 fw-semibold text-body">
                <i class="bi bi-broadcast-pin text-primary me-1" aria-hidden="true"></i>
                Linki do niezmontowanej transmisji na żywo z czatem
            </h2>
            <ul class="list-group list-group-flush online-lesson-live-recordings-list mb-0">
                @foreach($linkedCourseLiveVideos as $video)
                    @php
                        $watchUrl = $video->getWatchUrl();
                        $linkLabel = trim((string) ($video->title ?? ''));
                    @endphp
                    <li class="list-group-item bg-transparent px-0 py-2 border-0 @if(! $loop->last) mb-1 pb-2 border-bottom @endif">
                        @if($linkLabel !== '')
                            <div class="small fw-semibold text-body mb-1">{{ $linkLabel }}</div>
                        @endif
                        <a href="{{ $watchUrl }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="small text-break link-primary text-decoration-none">
                            <i class="bi bi-box-arrow-up-right me-1 opacity-75" aria-hidden="true"></i>{{ $watchUrl }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
