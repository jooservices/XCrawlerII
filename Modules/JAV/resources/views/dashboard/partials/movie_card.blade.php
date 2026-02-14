<div class="col">
    @php
        $hideActors = (bool) ($preferences['hide_actors'] ?? false);
        $hideTags = (bool) ($preferences['hide_tags'] ?? false);
        $textPreference = $preferences['text_preference'] ?? 'detailed';
        $title = $textPreference === 'concise' ? Str::limit($item->title, 45) : $item->title;
        $description = $textPreference === 'concise'
            ? Str::limit($item->description ?? 'No description available.', 120)
            : ($item->description ?? 'No description available.');
    @endphp

    <div class="card h-100 shadow-sm movie-card" data-uuid="{{ $item->uuid }}" data-id="{{ $item->id }}" style="cursor: pointer;">
        <div class="position-relative">
            <img src="{{ $item->cover }}" class="card-img-top" alt="{{ $item->code }}"
                onerror="this.src='https://placehold.co/300x400?text=No+Image'">
            <div class="position-absolute top-0 end-0 bg-dark bg-opacity-75 text-white px-2 py-1 m-2 rounded">
                <small><i class="fas fa-eye"></i> <span
                        class="view-count-{{ $item->id }}">{{ $item->views ?? 0 }}</span></small>
                <small class="ms-2"><i class="fas fa-download"></i> <span
                        class="download-count-{{ $item->id }}">{{ $item->downloads ?? 0 }}</span></small>
            </div>
        </div>

        <div class="card-body">
            <h5 class="card-title text-primary">{{ $item->formatted_code }}</h5>
            <p class="card-text text-truncate" title="{{ $item->title }}">{{ $title }}</p>
            <p class="card-text">
                <small class="text-muted"><i class="fas fa-calendar-alt"></i>
                    {{ \Carbon\Carbon::parse($item->date)->format('M d, Y') }}</small>
                @if($item->size)
                    <span class="float-end badge bg-secondary">{{ $item->size }} GB</span>
                @endif
            </p>

            @if(!$hideActors)
                <div class="mb-2">
                    @foreach($item->actors as $actor)
                        @php
                            $actorName = is_string($actor) ? $actor : (is_array($actor) ? $actor['name'] : $actor->name);
                        @endphp
                        <a href="{{ route('jav.blade.dashboard', ['actor' => $actorName]) }}"
                            class="badge bg-success text-decoration-none z-index-2 position-relative">{{ $actorName }}</a>
                    @endforeach
                </div>
            @endif

            @if(!$hideTags)
                <div>
                    @foreach($item->tags as $tag)
                        @php
                            $tagName = is_string($tag) ? $tag : (is_array($tag) ? $tag['name'] : $tag->name);
                        @endphp
                        <a href="{{ route('jav.blade.dashboard', ['tag' => $tagName]) }}"
                            class="badge bg-info text-dark text-decoration-none z-index-2 position-relative">{{ $tagName }}</a>
                    @endforeach
                </div>
            @endif

            <div class="mt-3 d-grid gap-2">
                <a href="{{ route('jav.movies.download', $item) }}"
                    class="btn btn-primary btn-sm download-btn z-index-2 position-relative"><i
                        class="fas fa-download"></i> Download</a>
            </div>

            @auth
                <div class="mt-2 d-flex gap-2">
                    <button type="button"
                        class="btn btn-sm {{ !empty($item->is_liked) ? 'btn-danger' : 'btn-outline-danger' }} quick-like-btn z-index-2 position-relative"
                        data-jav-id="{{ $item->id }}"
                        title="Like">
                        <i class="{{ !empty($item->is_liked) ? 'fas' : 'far' }} fa-heart"></i>
                    </button>
                    <button type="button"
                        class="btn btn-sm {{ !empty($item->in_watchlist) ? 'btn-warning' : 'btn-outline-warning' }} quick-watchlist-btn z-index-2 position-relative"
                        data-jav-id="{{ $item->id }}"
                        data-watchlist-id="{{ $item->watchlist_id ?? '' }}"
                        title="Watchlist">
                        <i class="{{ !empty($item->in_watchlist) ? 'fas' : 'far' }} fa-bookmark"></i>
                    </button>
                    <div class="quick-rating-group d-flex align-items-center ms-auto">
                        @for($star = 1; $star <= 5; $star++)
                            @php $activeStar = ($item->user_rating ?? 0) >= $star; @endphp
                            <button type="button"
                                class="btn btn-link btn-sm p-0 mx-1 quick-rate-btn {{ $activeStar ? 'text-warning' : 'text-secondary' }} z-index-2 position-relative"
                                data-jav-id="{{ $item->id }}"
                                data-rating="{{ $star }}"
                                data-rating-id="{{ $item->user_rating_id ?? '' }}"
                                title="Rate {{ $star }} star{{ $star > 1 ? 's' : '' }}">
                                <i class="fas fa-star"></i>
                            </button>
                        @endfor
                    </div>
                </div>
            @endauth
        </div>
        <div class="card-footer bg-transparent border-top-0">
            <button class="btn btn-sm btn-outline-secondary w-100 z-index-2 position-relative" type="button"
                data-bs-toggle="collapse" data-bs-target="#desc-{{ $item->id }}">
                Show Description
            </button>
            <div class="collapse mt-2" id="desc-{{ $item->id }}">
                <div class="card card-body small">
                    {{ $description }}
                </div>
            </div>
        </div>
    </div>
</div>
