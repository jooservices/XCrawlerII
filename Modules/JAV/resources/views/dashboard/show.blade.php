@extends('jav::layouts.dashboard')

@section('content')
    <div class="container-fluid">
        <!-- Movie Detail -->
        <div class="row mb-4">
            <div class="col-md-6">
                <img src="{{ $jav->cover }}" class="img-fluid rounded shadow" alt="{{ $jav->formatted_code }}"
                    onerror="this.src='https://placehold.co/400x600?text=No+Image'">
            </div>
            <div class="col-md-6">
                <h2 class="text-primary">{{ $jav->formatted_code }}</h2>
                <h4 class="text-muted">{{ $jav->title }}</h4>

                <div class="mt-3">
                    <p><strong><i class="fas fa-calendar-alt"></i> Date:</strong>
                        {{ \Carbon\Carbon::parse($jav->date)->format('M d, Y') }}</p>
                    @if($jav->size)
                        <p><strong><i class="fas fa-hdd"></i> Size:</strong> {{ $jav->size }} GB</p>
                    @endif
                    <p><strong><i class="fas fa-eye"></i> Views:</strong> <span id="viewCount">{{ $jav->views }}</span></p>
                    <p><strong><i class="fas fa-download"></i> Downloads:</strong> {{ $jav->downloads }}</p>
                </div>

                <!-- Actors -->
                <div class="mb-3">
                    <strong><i class="fas fa-users"></i> Actors:</strong><br>
                    @forelse($jav->actors as $actor)
                        <a href="{{ route('jav.dashboard', ['actor' => $actor->name]) }}"
                            class="badge bg-success text-decoration-none me-1 mb-1">{{ $actor->name }}</a>
                    @empty
                        <span class="text-muted">No actors listed</span>
                    @endforelse
                </div>

                <!-- Tags -->
                <div class="mb-3">
                    <strong><i class="fas fa-tags"></i> Tags:</strong><br>
                    @forelse($jav->tags as $tag)
                        <a href="{{ route('jav.dashboard', ['tag' => $tag->name]) }}"
                            class="badge bg-info text-dark text-decoration-none me-1 mb-1">{{ $tag->name }}</a>
                    @empty
                        <span class="text-muted">No tags listed</span>
                    @endforelse
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <strong><i class="fas fa-info-circle"></i> Description:</strong>
                    <p class="mt-2">{{ $jav->description ?? 'No description available.' }}</p>
                </div>

                <div class="mt-4">
                    @auth
                        <button id="likeBtn" class="btn btn-{{ $isLiked ? 'danger' : 'outline-danger' }} btn-lg me-2"
                            data-id="{{ $jav->id }}" data-type="jav" data-liked="{{ $isLiked ? '1' : '0' }}">
                            <i class="fas fa-heart"></i> {{ $isLiked ? 'Liked' : 'Like' }}
                        </button>
                    @endauth
                    <a href="{{ route('jav.movies.download', $jav) }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-download"></i> Download Torrent
                    </a>
                    <a href="{{ route('jav.dashboard') }}" class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <hr class="my-5">

        <!-- Related Movies by Actors -->
        @if($relatedByActors->isNotEmpty())
            <div class="row mb-5">
                <div class="col-12">
                    <h3><i class="fas fa-users"></i> Related Movies by Actors</h3>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4 mb-5">
                @foreach($relatedByActors as $item)
                    <div class="col">
                        <div class="card h-100 shadow-sm" style="cursor: pointer;"
                            onclick="window.location='{{ route('jav.movies.show', $item) }}'">
                            <div class="position-relative">
                                <img src="{{ $item->cover }}" class="card-img-top" alt="{{ $item->formatted_code }}"
                                    onerror="this.src='https://via.placeholder.com/300x400?text=No+Image'">
                                <div class="position-absolute top-0 end-0 bg-dark bg-opacity-75 text-white px-2 py-1 m-2 rounded">
                                    <small><i class="fas fa-eye"></i> {{ $item->views ?? 0 }}</small>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title text-primary">{{ $item->formatted_code }}</h6>
                                <p class="card-text text-truncate small" title="{{ $item->title }}">{{ $item->title }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Related Movies by Tags -->
        @if($relatedByTags->isNotEmpty())
            <div class="row mb-5">
                <div class="col-12">
                    <h3><i class="fas fa-tags"></i> Related Movies by Tags</h3>
                </div>
            </div>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
                @foreach($relatedByTags as $item)
                    <div class="col">
                        <div class="card h-100 shadow-sm" style="cursor: pointer;"
                            onclick="window.location='{{ route('jav.movies.show', $item) }}'">
                            <div class="position-relative">
                                <img src="{{ $item->cover }}" class="card-img-top" alt="{{ $item->formatted_code }}"
                                    onerror="this.src='https://via.placeholder.com/300x400?text=No+Image'">
                                <div class="position-absolute top-0 end-0 bg-dark bg-opacity-75 text-white px-2 py-1 m-2 rounded">
                                    <small><i class="fas fa-eye"></i> {{ $item->views ?? 0 }}</small>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title text-primary">{{ $item->formatted_code }}</h6>
                                <p class="card-text text-truncate small" title="{{ $item->title }}">{{ $item->title }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const likeBtn = document.getElementById('likeBtn');
                if (likeBtn) {
                    likeBtn.addEventListener('click', function () {
                        const id = this.dataset.id;
                        const type = this.dataset.type;
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                        fetch('{{ route('jav.toggle-like') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify({ id, type })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    this.dataset.liked = data.liked ? '1' : '0';
                                    if (data.liked) {
                                        this.classList.remove('btn-outline-danger');
                                        this.classList.add('btn-danger');
                                        this.innerHTML = '<i class="fas fa-heart"></i> Liked';
                                    } else {
                                        this.classList.remove('btn-danger');
                                        this.classList.add('btn-outline-danger');
                                        this.innerHTML = '<i class="fas fa-heart"></i> Like';
                                    }
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    });
                }
            });
        </script>
    @endpush
@endsection