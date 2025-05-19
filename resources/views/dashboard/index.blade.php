@extends('layouts.app')

@section('content')
<div class="container pt-3">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-3">
                    <div class="card mb-3">
                        <div class="card-header">{{ __('MENU') }}</div>
                        <div class="card-body p-0">
                            <ul class="nav nav-pills flex-column">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard') }}" class="nav-link active">{{ __('Panel') }}</a>
                                </li>
                                <hr class="my-0">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.szkolenia') }}" class="nav-link">{{ __('Moje szkolenia') }}</a>
                                </li>
                                <hr class="my-0">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.zaswiadczenia') }}" class="nav-link">{{ __('Zaświadczenia') }}</a>
                                </li>
                                <hr class="my-0">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.moje-dane') }}" class="nav-link">{{ __('Moje dane') }}</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header">{{ __('Panel użytkownika') }}</div>

                        <div class="card-body">
                            {{ __("You're logged in!") }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
