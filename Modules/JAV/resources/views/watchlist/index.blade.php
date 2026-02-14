@extends('jav::layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-3"><i class="fas fa-bookmark me-2"></i>My Watchlist</h1>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>
    </div>

    <!-- Status Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="{{ route('watchlist.index', ['status' => 'all']) }}"
                   class="btn btn-{{ $status === 'all' ? 'primary' : 'outline-primary' }}">
                    <i class="fas fa-list me-1"></i>All ({{ $watchlist->total() }})
                </a>
                <a href="{{ route('watchlist.index', ['status' => 'to_watch']) }}"
                   class="btn btn-{{ $status === 'to_watch' ? 'primary' : 'outline-primary' }}">
                    <i class="fas fa-clock me-1"></i>To Watch
                </a>
                <a href="{{ route('watchlist.index', ['status' => 'watching']) }}"
                   class="btn btn-{{ $status === 'watching' ? 'primary' : 'outline-primary' }}">
                    <i class="fas fa-play me-1"></i>Watching
                </a>
                <a href="{{ route('watchlist.index', ['status' => 'watched']) }}"
                   class="btn btn-{{ $status === 'watched' ? 'primary' : 'outline-primary' }}">
                    <i class="fas fa-check me-1"></i>Watched
                </a>
            </div>
        </div>
    </div>

    <!-- Watchlist Items -->
    @if($watchlist->count() > 0)
        <div class="row">
            @foreach($watchlist as $item)
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <img src="{{ $item->jav->cover }}" class="card-img-top" alt="{{ $item->jav->title }}" loading="lazy">
                        <div class="card-body">
                            <h6 class="card-title">
                                <a href="{{ route('jav.movies.show', $item->jav) }}" class="text-decoration-none">
                                    {{ Str::limit($item->jav->title, 50) }}
                                </a>
                            </h6>
                            <p class="card-text">
                                <small class="text-muted">{{ $item->jav->code }}</small>
                            </p>

                            <!-- Status Badge -->
                            <div class="mb-2">
                                @if($item->status === 'to_watch')
                                    <span class="badge bg-info">To Watch</span>
                                @elseif($item->status === 'watching')
                                    <span class="badge bg-warning">Watching</span>
                                @else
                                    <span class="badge bg-success">Watched</span>
                                @endif
                            </div>

                            <!-- Change Status -->
                            <form method="POST" action="{{ route('watchlist.update', $item) }}" class="mb-2">
                                @csrf
                                @method('PUT')
                                <div class="input-group input-group-sm">
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="to_watch" {{ $item->status === 'to_watch' ? 'selected' : '' }}>To Watch</option>
                                        <option value="watching" {{ $item->status === 'watching' ? 'selected' : '' }}>Watching</option>
                                        <option value="watched" {{ $item->status === 'watched' ? 'selected' : '' }}>Watched</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                </div>
                            </form>

                            <!-- Remove from Watchlist -->
                            <form method="POST" action="{{ route('watchlist.destroy', $item) }}" onsubmit="return confirm('Remove from watchlist?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger w-100">
                                    <i class="fas fa-trash me-1"></i>Remove
                                </button>
                            </form>

                            <div class="mt-2">
                                <small class="text-muted">Added: {{ $item->created_at->diffForHumans() }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $watchlist->links() }}
        </div>
    @else
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-bookmark fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Your watchlist is empty</h5>
                <p class="text-muted">Start adding movies to your watchlist to keep track of what you want to watch!</p>
                <a href="{{ route('jav.dashboard') }}" class="btn btn-primary">
                    <i class="fas fa-film me-1"></i>Browse Movies
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
