<div class="col">
    <a href="{{ route('jav.blade.dashboard', ['tag' => $tag->name]) }}" class="text-decoration-none text-dark">
        <div class="card h-100 shadow-sm hover-shadow">
            <div class="card-body text-center">
                <i class="fas fa-tag fa-2x text-info mb-3"></i>
                <h5 class="card-title text-truncate" title="{{ $tag->name }}">{{ $tag->name }}</h5>
                <span class="badge bg-secondary">{{ $tag->javs_count ?? 0 }} JAVs</span>
            </div>
        </div>
    </a>
</div>