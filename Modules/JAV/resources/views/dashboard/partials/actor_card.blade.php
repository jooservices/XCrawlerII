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