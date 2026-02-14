<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <button class="btn btn-dark me-2" id="sidebarToggle" type="button">
            <i class="fas fa-bars"></i>
        </button>
        <a class="navbar-brand" href="{{ route('jav.blade.dashboard') }}">JAV Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            @php
                $searchAction = route('jav.blade.dashboard');
                $placeholder = 'Search movies...';
                if (request()->routeIs('jav.actors')) {
                    $searchAction = route('jav.blade.actors');
                    $placeholder = 'Search actors...';
                } elseif (request()->routeIs('jav.tags')) {
                    $searchAction = route('jav.blade.tags');
                    $placeholder = 'Search tags...';
                }
            @endphp
            <form action="{{ $searchAction }}" method="GET" class="d-flex me-auto" style="max-width: 400px;"
                id="searchForm">
                <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="{{ $placeholder }}"
                    aria-label="Search" value="{{ request('q') }}">

                @if(request()->routeIs('jav.dashboard'))
                    @php
                        $persistActor = $filters['actor'] ?? request('actor');
                        $persistTags = $filters['tags'] ?? (array) request('tags', []);
                        $persistTagsMode = $filters['tags_mode'] ?? request('tags_mode');
                        $persistAge = $filters['age'] ?? request('age');
                        $persistAgeMin = $filters['age_min'] ?? request('age_min');
                        $persistAgeMax = $filters['age_max'] ?? request('age_max');
                        $persistBioKey = $filters['bio_key'] ?? request('bio_key');
                        $persistBioValue = $filters['bio_value'] ?? request('bio_value');
                        $persistBioFilters = $filters['bio_filters'] ?? (array) request('bio_filters', []);
                    @endphp
                    @if($persistActor)
                        <input type="hidden" name="actor" value="{{ $persistActor }}">
                    @endif
                    @if(request('tag'))
                        <input type="hidden" name="tag" value="{{ request('tag') }}">
                    @endif
                    @foreach((array) $persistTags as $selectedTag)
                        @if(is_string($selectedTag) && trim($selectedTag) !== '')
                            <input type="hidden" name="tags[]" value="{{ $selectedTag }}">
                        @endif
                    @endforeach
                    @if($persistTagsMode)
                        <input type="hidden" name="tags_mode" value="{{ $persistTagsMode }}">
                    @endif
                    @if($persistAge)
                        <input type="hidden" name="age" value="{{ $persistAge }}">
                    @endif
                    @if($persistAgeMin)
                        <input type="hidden" name="age_min" value="{{ $persistAgeMin }}">
                    @endif
                    @if($persistAgeMax)
                        <input type="hidden" name="age_max" value="{{ $persistAgeMax }}">
                    @endif
                    @if($persistBioKey)
                        <input type="hidden" name="bio_key" value="{{ $persistBioKey }}">
                    @endif
                    @if($persistBioValue)
                        <input type="hidden" name="bio_value" value="{{ $persistBioValue }}">
                    @endif
                    @foreach((array) $persistBioFilters as $bioIndex => $bioFilter)
                        @php
                            $filterKey = is_array($bioFilter) ? trim((string) ($bioFilter['key'] ?? '')) : '';
                            $filterValue = is_array($bioFilter) ? trim((string) ($bioFilter['value'] ?? '')) : '';
                        @endphp
                        @if($filterKey !== '' || $filterValue !== '')
                            <input type="hidden" name="bio_filters[{{ $bioIndex }}][key]" value="{{ $filterKey }}">
                            <input type="hidden" name="bio_filters[{{ $bioIndex }}][value]" value="{{ $filterValue }}">
                        @endif
                    @endforeach
                    <input type="hidden" name="sort" value="{{ request('sort') }}" id="sortInput">
                    <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}" id="directionInput">
                    <input type="hidden" name="preset" value="{{ request('preset', 'default') }}" id="presetInput">
                    @if(request()->filled('saved_preset'))
                        <input type="hidden" name="saved_preset" value="{{ request('saved_preset') }}">
                    @endif
                @endif

                <button class="btn btn-outline-light btn-sm" type="submit"><i class="fas fa-search"></i></button>
            </form>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('jav.blade.dashboard') }}">Home</a>
                </li>
                @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">Register</a>
                    </li>
                @else
                    @php
                        $unreadNotifications = Auth::user()->javNotifications()
                            ->with('jav:id,uuid,code,title')
                            ->unread()
                            ->latest('id')
                            ->limit(8)
                            ->get();
                        $unreadCount = Auth::user()->javNotifications()->unread()->count();
                    @endphp
                    <li class="nav-item dropdown me-2">
                        <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                            <i class="fas fa-bell"></i>
                            @if($unreadCount > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                </span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown"
                            style="min-width: 340px;">
                            <li class="dropdown-header d-flex justify-content-between align-items-center">
                                <span>Notifications</span>
                                @if($unreadCount > 0)
                                    <form action="{{ route('jav.notifications.read-all') }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0">Mark all read</button>
                                    </form>
                                @endif
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            @forelse($unreadNotifications as $notification)
                                <li class="px-3 py-2 border-bottom">
                                    <div class="fw-semibold">{{ $notification->title }}</div>
                                    @if($notification->jav)
                                        <div class="small text-muted mb-1">
                                            <a href="{{ route('jav.blade.movies.show', $notification->jav) }}" class="text-decoration-none">
                                                {{ $notification->jav->code }} {{ $notification->jav->title }}
                                            </a>
                                        </div>
                                    @endif
                                    @php
                                        $payload = $notification->payload ?? [];
                                        $matchedActors = $payload['matched_actors'] ?? [];
                                        $matchedTags = $payload['matched_tags'] ?? [];
                                    @endphp
                                    @if(!empty($matchedActors))
                                        <div class="small">Actor: {{ implode(', ', $matchedActors) }}</div>
                                    @endif
                                    @if(!empty($matchedTags))
                                        <div class="small">Tag: {{ implode(', ', $matchedTags) }}</div>
                                    @endif
                                    <form action="{{ route('jav.notifications.read', $notification) }}" method="POST" class="mt-1">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0">Mark as read</button>
                                    </form>
                                </li>
                            @empty
                                <li class="px-3 py-2 text-muted small">No unread notifications</li>
                            @endforelse
                        </ul>
                    </li>
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
