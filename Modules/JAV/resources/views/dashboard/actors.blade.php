@extends('jav::layouts.dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Actors</h2>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form action="{{ route('jav.dashboard.actors') }}" method="GET" class="d-flex">
                    <input type="text" name="q" class="form-control me-2" placeholder="Search actors..."
                        value="{{ $query ?? '' }}">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
        </div>

        <!-- Actors List -->
        <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-4">
            @forelse($actors as $actor)
                <div class="col">
                    <a href="{{ route('jav.dashboard', ['actor' => $actor->name]) }}" class="text-decoration-none text-dark">
                        <div class="card h-100 shadow-sm hover-shadow">
                            <div class="card-body text-center">
                                <i class="fas fa-user-circle fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title text-truncate" title="{{ $actor->name }}">{{ $actor->name }}</h5>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        No actors found.
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $actors->withQueryString()->links() }}
        </div>
    </div>
@endsection