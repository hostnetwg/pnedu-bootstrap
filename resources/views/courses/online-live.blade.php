@extends('layouts.app')

@section('title', 'Szkolenia online LIVE - Platforma Nowoczesnej Edukacji')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Szkolenia online LIVE</h1>
            
            @if(!isset($databaseError) || !$databaseError)
                <div class="mb-3">
                    <span class="fw-semibold">
                        Wyświetlono
                        @if($courses->total() > 0)
                            {{ ($courses->currentPage() - 1) * $courses->perPage() + 1 }}
                            -
                            {{ ($courses->currentPage() - 1) * $courses->perPage() + $courses->count() }}
                            z
                            {{ $courses->total() }}
                            szkoleń
                        @else
                            0 szkoleń
                        @endif
                    </span>
                </div>
            @endif
            
            <div class="mb-4">
                <form method="GET" action="" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="instructor" class="form-label">Trener</label>
                        <select name="instructor" id="instructor" class="form-select" onchange="this.form.submit()">
                            <option value="">Wszyscy trenerzy</option>
                            @foreach($instructors as $instructor)
                                <option value="{{ $instructor->id }}" @if(isset($instructorId) && $instructorId == $instructor->id) selected @endif>
                                    {{ $instructor->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="date_filter" class="form-label">Data szkolenia</label>
                        <select name="date_filter" id="date_filter" class="form-select" onchange="this.form.submit()">
                            <option value="all" @if(empty($dateFilter) || $dateFilter === 'all') selected @endif>Wszystkie</option>
                            <option value="upcoming" @if(isset($dateFilter) && $dateFilter === 'upcoming') selected @endif>Nadchodzące</option>
                            <option value="archived" @if(isset($dateFilter) && $dateFilter === 'archived') selected @endif>Archiwalne</option>
                        </select>
                    </div>
                    <input type="hidden" name="sort" value="{{ $sort ?? 'desc' }}">
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filtruj</button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>
                                        Data rozpoczęcia
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => (isset($sort) && $sort === 'asc') ? 'desc' : 'asc', 'page' => 1]) }}" class="ms-1 text-decoration-none">
                                            @if(isset($sort) && $sort === 'asc')
                                                <i class="bi bi-caret-up-fill"></i>
                                            @else
                                                <i class="bi bi-caret-down-fill"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Data zakończenia</th>
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
                                    @php
                                        $now = now();
                                    @endphp
                                    @forelse ($courses as $course)
                                        @php
                                            $start = \Carbon\Carbon::parse($course->start_date);
                                            $end = $course->end_date ? \Carbon\Carbon::parse($course->end_date) : null;
                                            if ($start->gt($now)) {
                                                $rowClass = 'table-success'; // nadchodzące
                                            } elseif ($end && $end->lt($now)) {
                                                $rowClass = 'table-secondary text-muted'; // archiwalne
                                            } else {
                                                $rowClass = 'table-info'; // w trakcie
                                            }
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td>{{ $course->formatted_date }}</td>
                                            <td>{{ $course->end_date ? date('d.m.Y H:i', strtotime($course->end_date)) : '-' }}</td>
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
                    @if(!isset($databaseError) || !$databaseError)
                        <div class="d-flex justify-content-center mt-4">
                            {{ $courses->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection