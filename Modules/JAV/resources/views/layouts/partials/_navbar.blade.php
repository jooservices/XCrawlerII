<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <button class="btn btn-dark me-2" id="sidebarToggle" type="button">
            <i class="fas fa-bars"></i>
        </button>
        <a class="navbar-brand" href="{{ route('jav.dashboard') }}">JAV Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            @php
                $searchAction = route('jav.dashboard');
                $placeholder = 'Search movies...';
                if (request()->routeIs('jav.actors')) {
                    $searchAction = route('jav.actors');
                    $placeholder = 'Search actors...';
                } elseif (request()->routeIs('jav.tags')) {
                    $searchAction = route('jav.tags');
                    $placeholder = 'Search tags...';
                }
            @endphp
            <form action="{{ $searchAction }}" method="GET" class="d-flex me-auto" style="max-width: 400px;"
                id="searchForm">
                <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="{{ $placeholder }}"
                    aria-label="Search" value="{{ request('q') }}">

                @if(request()->routeIs('jav.dashboard'))
                    @if(request('actor'))
                        <input type="hidden" name="actor" value="{{ request('actor') }}">
                    @endif
                    @if(request('tag'))
                        <input type="hidden" name="tag" value="{{ request('tag') }}">
                    @endif
                    <input type="hidden" name="sort" value="{{ request('sort') }}" id="sortInput">
                    <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}" id="directionInput">
                @endif

                <button class="btn btn-outline-light btn-sm" type="submit"><i class="fas fa-search"></i></button>
            </form>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('jav.dashboard') }}">Home</a>
                </li>
                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">Register</a>
                    </li>
                @else
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>