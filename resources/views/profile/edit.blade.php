@extends('layouts.app')

@section('content')
<div class="container pt-3 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-3">{{ __('Edytuj profil') }}</h2>
            <div class="mb-4">
                @include('profile.partials.update-profile-information-form')
            </div>
            <div class="mb-4">
                @include('profile.partials.update-password-form')
            </div>
            <div>
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
@endsection
