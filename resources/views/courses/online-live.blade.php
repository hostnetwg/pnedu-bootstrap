@extends('layouts.app')

@section('title', 'Szkolenia online LIVE - Platforma Nowoczesnej Edukacji')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Szkolenia online LIVE</h1>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tytuł</th>
                                    <th>Trener</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($databaseError) && $databaseError)
                                    <tr>
                                        <td colspan="3" class="text-center text-danger">
                                            <div class="alert alert-danger">
                                                Przepraszamy, wystąpił problem z dostępem do bazy danych. Prosimy spróbować później.
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                    @forelse ($courses as $course)
                                        <tr>
                                            <td>{{ $course->formatted_date }}</td>
                                            <td>{{ $course->title }}</td>
                                            <td>{{ $course->trainer }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center">Brak dostępnych szkoleń</td>
                                        </tr>
                                    @endforelse
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection