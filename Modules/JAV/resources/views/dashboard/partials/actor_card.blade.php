<div class="col">
    <div class="card h-100 shadow-sm hover-shadow">
        <div class="position-relative">
            <img src="{{ $actor->cover }}" class="card-img-top" alt="{{ $actor->name }}"
                onerror="this.src='https://placehold.co/300x400?text=No+Image'">
        </div>
        <div class="card-body text-center">
            <h5 class="card-title text-truncate" title="{{ $actor->name }}">{{ $actor->name }}</h5>
            <span class="badge bg-secondary">{{ $actor->javs_count ?? 0 }} JAVs</span>
            <div class="mt-3 d-grid gap-2">
                <a href="{{ route('jav.actors.bio', $actor) }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-id-card me-1"></i> Bio
                </a>
                <a href="{{ route('jav.dashboard', ['actor' => $actor->name]) }}" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-film me-1"></i> JAVs
                </a>
            </div>
        </div>
    </div>
</div>
