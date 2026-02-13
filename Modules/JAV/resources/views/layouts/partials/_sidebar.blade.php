<div class="sidebar" id="sidebar">
    <a href="{{ route('jav.dashboard') }}"
        class="{{ request()->routeIs('jav.dashboard') && !request()->routeIs('jav.actors') && !request()->routeIs('jav.tags') ? 'active' : '' }}">
        <i class="fas fa-film me-2"></i> Movies
    </a>
    <a href="{{ route('jav.actors') }}" class="{{ request()->routeIs('jav.actors') ? 'active' : '' }}">
        <i class="fas fa-users me-2"></i> Actors
    </a>
    <a href="{{ route('jav.tags') }}" class="{{ request()->routeIs('jav.tags') ? 'active' : '' }}">
        <i class="fas fa-tags me-2"></i> Tags
    </a>

    @auth
        <hr class="border-secondary my-2">
        <a href="{{ route('jav.recommendations') }}"
            class="{{ request()->routeIs('jav.recommendations') ? 'active' : '' }}">
            <i class="fas fa-star me-2"></i> Recommendations
        </a>
        <a href="{{ route('jav.history') }}" class="{{ request()->routeIs('jav.history') ? 'active' : '' }}">
            <i class="fas fa-history me-2"></i> History
        </a>
        <a href="{{ route('jav.favorites') }}" class="{{ request()->routeIs('jav.favorites') ? 'active' : '' }}">
            <i class="fas fa-heart me-2"></i> Favorites
        </a>
    @endauth
</div>