@extends('layouts.app')

@section('title', 'Regulamin – Platforma Nowoczesnej Edukacji')
@section('meta_description', 'Regulamin pnedu.pl: zasady zamawiania szkoleń, płatności, realizacji, odstąpienia i reklamacji.')

@section('content')
@php
    $version = $termsVersion ?? config('legal.terms.current_version');
    $definition = config("legal.terms.versions.{$version}");
    abort_unless(is_array($definition), 404);
@endphp
<section class="py-5">
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="mb-0">Regulamin pnedu.pl</h1>
                <p class="small text-muted mb-0">
                    Wersja {{ $version }} · obowiązuje od
                    {{ \Carbon\Carbon::parse($definition['effective_at'])->locale('pl')->translatedFormat('j F Y') }} r.
                    · <a href="{{ route('regulamin.pdf', ['version' => $version]) }}">Pobierz PDF</a>
                </p>
            </div>
            <div class="card-body">
                @include($definition['view'])
            </div>
        </div>
    </div>
</section>
@endsection
