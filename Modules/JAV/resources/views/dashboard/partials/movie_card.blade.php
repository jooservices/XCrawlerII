<div class="col">
    <div class="card h-100 shadow-sm movie-card" data-uuid="{{ $item->uuid }}" style="cursor: pointer;">
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
                        class="badge bg-success text-decoration-none z-index-2 position-relative">{{ $actorName }}</a>
                @endforeach
            </div>

            <!-- Tags -->
            <div>
                @foreach($item->tags as $tag)
                    @php
                        $tagName = is_string($tag) ? $tag : (is_array($tag) ? $tag['name'] : $tag->name);
                    @endphp
                    <a href="{{ route('jav.dashboard', ['tag' => $tagName]) }}"
                        class="badge bg-info text-dark text-decoration-none z-index-2 position-relative">{{ $tagName }}</a>
                @endforeach
            </div>

            <div class="mt-3 d-grid gap-2">
                <a href="{{ route('jav.movies.download', $item) }}"
                    class="btn btn-primary btn-sm download-btn z-index-2 position-relative"><i
                        class="fas fa-download"></i> Download</a>
            </div>
        </div>
        <div class="card-footer bg-transparent border-top-0">
            <button class="btn btn-sm btn-outline-secondary w-100 z-index-2 position-relative" type="button"
                data-bs-toggle="collapse" data-bs-target="#desc-{{ $item->id }}">
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