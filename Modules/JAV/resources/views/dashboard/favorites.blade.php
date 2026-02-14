@extends('jav::layouts.dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-heart text-danger"></i> My Favorites</h2>
                <p class="text-muted">Movies, actors, and tags you've liked</p>
            </div>
        </div>

        @if($favorites->isEmpty())
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You haven't liked anything yet. Start exploring and save your favorites!
            </div>
        @else
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
                @foreach($favorites as $favorite)
                    @if($favorite->favoritable_type === 'Modules\\JAV\\Models\\Jav')
                        <div class="col">
                            <div class="card h-100 shadow-sm" style="cursor: pointer;"
                                onclick="window.location='{{ route('jav.blade.movies.show', $favorite->favoritable) }}'">
                                <div class="position-relative">
                                    <img src="{{ $favorite->favoritable->cover }}" class="card-img-top"
                                        alt="{{ $favorite->favoritable->formatted_code }}"
                                        onerror="this.src='https://placehold.co/300x400?text=No+Image'">
                                    <div class="position-absolute top-0 start-0 bg-danger text-white px-2 py-1 m-2 rounded">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title text-primary">{{ $favorite->favoritable->formatted_code }}</h6>
                                    <p class="card-text text-truncate small" title="{{ $favorite->favoritable->title }}">
                                        {{ $favorite->favoritable->title }}</p>
                                    <small class="text-muted">Liked {{ $favorite->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                    @elseif($favorite->favoritable_type === 'Modules\\JAV\\Models\\Actor')
                        <div class="col">
                            <div class="card h-100 shadow-sm bg-success bg-opacity-10" style="cursor: pointer;"
                                onclick="window.location='{{ route('jav.blade.dashboard', ['actor' => $favorite->favoritable->name]) }}'">
                                <div class="card-body text-center">
                                    <i class="fas fa-user fa-4x text-success mb-3"></i>
                                    <h5 class="card-title">{{ $favorite->favoritable->name }}</h5>
                                    <span class="badge bg-success"><i class="fas fa-users"></i> Actor</span>
                                    <p class="text-muted small mt-2">Liked {{ $favorite->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </div>
                    @elseif($favorite->favoritable_type === 'Modules\\JAV\\Models\\Tag')
                        <div class="col">
                            <div class="card h-100 shadow-sm bg-info bg-opacity-10" style="cursor: pointer;"
                                onclick="window.location='{{ route('jav.blade.dashboard', ['tag' => $favorite->favoritable->name]) }}'">
                                <div class="card-body text-center">
                                    <i class="fas fa-tag fa-4x text-info mb-3"></i>
                                    <h5 class="card-title">{{ $favorite->favoritable->name }}</h5>
                                    <span class="badge bg-info"><i class="fas fa-tags"></i> Tag</span>
                                    <p class="text-muted small mt-2">Liked {{ $favorite->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $favorites->links() }}
            </div>
        @endif
    </div>
@endsection