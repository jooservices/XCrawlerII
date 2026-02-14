@extends('jav::layouts.dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-magic text-purple"></i> Recommended for You</h2>
                <p class="text-muted">Based on your liked movies, actors, and tags</p>
            </div>
        </div>

        @if($recommendations->isEmpty())
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No recommendations yet. Like some movies, actors, or tags to get personalized suggestions!
            </div>
        @else
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
                @foreach($recommendations as $recommendation)
                    @php
                        $item = $recommendation['movie'];
                        $reasons = $recommendation['reasons'] ?? ['actors' => [], 'tags' => []];
                    @endphp
                    <div class="col">
                        <div class="card h-100 shadow-sm" style="cursor: pointer;" onclick="window.location='{{ route('jav.blade.movies.show', $item) }}'">
                            <div class="position-relative">
                                <img src="{{ $item->cover }}" class="card-img-top" alt="{{ $item->formatted_code }}" onerror="this.src='https://placehold.co/300x400?text=No+Image'">
                                <div class="position-absolute top-0 end-0 bg-dark bg-opacity-75 text-white px-2 py-1 m-2 rounded">
                                    <small><i class="fas fa-eye"></i> {{ $item->views ?? 0 }}</small>
                                </div>
                                <div class="position-absolute top-0 start-0 bg-purple bg-opacity-75 text-white px-2 py-1 m-2 rounded">
                                    <small><i class="fas fa-star"></i></small>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title text-primary">{{ $item->formatted_code }}</h6>
                                <p class="card-text text-truncate small" title="{{ $item->title }}">{{ $item->title }}</p>
                                @if(!empty($reasons['actors']) || !empty($reasons['tags']))
                                    <div class="mb-2">
                                        @foreach($reasons['actors'] as $actorName)
                                            <span class="badge bg-success">Because you liked actor: {{ $actorName }}</span>
                                        @endforeach
                                        @foreach($reasons['tags'] as $tagName)
                                            <span class="badge bg-info text-dark">Because you liked tag: {{ $tagName }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="mt-2">
                                    @foreach($item->actors->take(2) as $actor)
                                        <span class="badge bg-success text-xs">{{ $actor->name }}</span>
                                    @endforeach
                                    @if($item->actors->count() > 2)
                                        <span class="badge bg-secondary text-xs">+{{ $item->actors->count() - 2 }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
