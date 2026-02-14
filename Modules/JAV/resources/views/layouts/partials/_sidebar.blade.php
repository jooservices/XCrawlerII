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
        <a href="{{ route('watchlist.index') }}" class="{{ request()->routeIs('watchlist.*') ? 'active' : '' }}">
            <i class="fas fa-bookmark me-2"></i> Watchlist
        </a>

        @if(auth()->user()->hasAnyRole(['admin', 'moderator']))
            <hr class="border-secondary my-2">
            <div class="px-3 py-2 text-uppercase" style="font-size: 0.75rem; color: #aaa;">
                <i class="fas fa-cog me-2"></i> Administration
            </div>
            @if(auth()->user()->hasPermission('view-users'))
                <a href="{{ route('admin.users.index') }}"
                    class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users-cog me-2"></i> Users
                </a>
            @endif
            @if(auth()->user()->hasPermission('view-roles'))
                <a href="{{ route('admin.roles.index') }}"
                    class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <i class="fas fa-shield-alt me-2"></i> Roles
                </a>
            @endif
            @if(auth()->user()->hasRole('admin'))
                <a href="{{ route('jav.admin.sync-progress') }}"
                    class="{{ request()->routeIs('jav.admin.sync-progress*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line me-2"></i> Sync Progress
                </a>
                <a href="{{ route('jav.admin.search-quality.index') }}"
                    class="{{ request()->routeIs('jav.admin.search-quality.*') ? 'active' : '' }}">
                    <i class="fas fa-search me-2"></i> Search Quality
                </a>
                <a href="{{ route('jav.admin.provider-sync.index') }}"
                    class="{{ request()->routeIs('jav.admin.provider-sync.*') ? 'active' : '' }}">
                    <i class="fas fa-sync-alt me-2"></i> Provider Sync
                </a>
            @endif
        @endif
    @endauth
</div>
