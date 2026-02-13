@extends('jav::layouts.dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Tags</h2>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form action="{{ route('jav.dashboard.tags') }}" method="GET" class="d-flex">
                    <input type="text" name="q" class="form-control me-2" placeholder="Search tags..."
                        value="{{ $query ?? '' }}">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
        </div>

        <!-- Tags List -->
        <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-4">
            @forelse($tags as $tag)
                <div class="col">
                    <a href="{{ route('jav.dashboard', ['tag' => $tag->name]) }}" class="text-decoration-none text-dark">
                        <div class="card h-100 shadow-sm hover-shadow">
                            <div class="card-body text-center">
                                <i class="fas fa-tag fa-2x text-info mb-3"></i>
                                <h5 class="card-title text-truncate" title="{{ $tag->name }}">{{ $tag->name }}</h5>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        No tags found.
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $tags->withQueryString()->links() }}
        </div>
    </div>
@endsection