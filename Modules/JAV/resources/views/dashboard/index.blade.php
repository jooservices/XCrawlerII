@extends('jav::layouts.dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Movies</h2>
                @if(request('actor'))
                    <span class="badge bg-primary fs-5">Actor: {{ request('actor') }} <a href="{{ route('jav.dashboard') }}"
                            class="text-white ms-2"><i class="fas fa-times"></i></a></span>
                @endif
                @if(request('tag'))
                    <span class="badge bg-info fs-5">Tag: {{ request('tag') }} <a href="{{ route('jav.dashboard') }}"
                            class="text-white ms-2"><i class="fas fa-times"></i></a></span>
                @endif
            </div>
        </div>

        <!-- Search Bar -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form action="{{ route('jav.dashboard') }}" method="GET" class="d-flex">
                    <input type="text" name="q" class="form-control me-2" placeholder="Search movies..."
                        value="{{ $query ?? '' }}">
                    @if(request('actor'))
                        <input type="hidden" name="actor" value="{{ request('actor') }}">
                    @endif
                    @if(request('tag'))
                        <input type="hidden" name="tag" value="{{ request('tag') }}">
                    @endif
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
        </div>

        <!-- Movies Grid -->
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            @forelse($items as $item)
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        @if($showCover)
                            <img src="{{ $item->image }}" class="card-img-top" alt="{{ $item->code }}"
                                onerror="this.src='https://via.placeholder.com/300x400?text=No+Image'">
                        @else
                            <img src="https://via.placeholder.com/300x400?text=Cover+Hidden" class="card-img-top"
                                alt="Cover Hidden">
                        @endif
                        <div class="card-body">
                            <h5 class="card-title text-primary">{{ $item->code }}</h5>
                            <p class="card-text text-truncate" title="{{ $item->title }}">{{ $item->title }}</p>
                            <p class="card-text">
                                <small class="text-muted"><i class="fas fa-calendar-alt"></i>
                                    {{ \Carbon\Carbon::parse($item->date)->format('M d, Y') }}</small>
                                @if($item->size)
                                    <span class="float-end badge bg-secondary">{{ $item->size }} GB</span>
                                @endif
                            </p>

                            <!-- Actors -->
                            <div class="mb-2">
                                @foreach($item->actors as $actor)
                                    @php
                                        $actorName = is_string($actor) ? $actor : (is_array($actor) ? $actor['name'] : $actor->name);
                                    @endphp
                                    <a href="{{ route('jav.dashboard', ['actor' => $actorName]) }}"
                                        class="badge bg-success text-decoration-none">{{ $actorName }}</a>
                                @endforeach
                            </div>

                            <!-- Tags -->
                            <div>
                                @foreach($item->tags as $tag)
                                    @php
                                        $tagName = is_string($tag) ? $tag : (is_array($tag) ? $tag['name'] : $tag->name);
                                    @endphp
                                    <a href="{{ route('jav.dashboard', ['tag' => $tagName]) }}"
                                        class="badge bg-info text-dark text-decoration-none">{{ $tagName }}</a>
                                @endforeach
                            </div>

                            <div class="mt-3 d-grid gap-2">
                                <a href="{{ route('jav.dashboard.download', $item->id) }}" class="btn btn-primary btn-sm"><i
                                        class="fas fa-download"></i> Download</a>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top-0">
                            <button class="btn btn-sm btn-outline-secondary w-100" type="button" data-bs-toggle="collapse"
                                data-bs-target="#desc-{{ $item->id }}">
                                Show Description
                            </button>
                            <div class="collapse mt-2" id="desc-{{ $item->id }}">
                                <div class="card card-body small">
                                    {{ $item->description ?? 'No description available.' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        No movies found.
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $items->withQueryString()->links() }}
        </div>
    </div>
@endsection