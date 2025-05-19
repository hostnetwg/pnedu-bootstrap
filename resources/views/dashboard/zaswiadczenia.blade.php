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
                                    <a href="{{ route('dashboard') }}" class="nav-link">{{ __('Panel') }}</a>
                                </li>
                                <hr class="my-0">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.szkolenia') }}" class="nav-link">{{ __('Moje szkolenia') }}</a>
                                </li>
                                <hr class="my-0">
                                <li class="nav-item">
                                    <a href="{{ route('dashboard.zaswiadczenia') }}" class="nav-link active">{{ __('Zaświadczenia') }}</a>
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
                        <div class="card-header">{{ __('Zaświadczenia') }}</div>
                        <div class="card-body">
                            <p>Tu znajduje się przykładowa treść podstrony Zaświadczenia. Możesz ją teraz dopracować według potrzeb.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection