{{-- Google Tag Manager (noscript) — zaraz po otwarciu <body> --}}
@production
    @php
        $gtmId = config('services.google_tag_manager.id');
    @endphp
    @if(!empty($gtmId))
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}"
            height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
    @endif
@endproduction
