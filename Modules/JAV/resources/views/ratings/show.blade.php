@extends('jav::layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-star me-2"></i>Rating Details</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <h5>Movie</h5>
                        <a href="{{ route('jav.movies.show', $rating->jav) }}">
                            {{ $rating->jav->title }}
                        </a>
                    </div>

                    <div class="mb-3">
                        <h5>Rating</h5>
                        <div>
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= $rating->rating ? 'text-warning' : 'text-muted' }}"></i>
                            @endfor
                            <span class="ms-2">{{ $rating->rating }}/5</span>
                        </div>
                    </div>

                    @if($rating->review)
                        <div class="mb-3">
                            <h5>Review</h5>
                            <p>{{ $rating->review }}</p>
                        </div>
                    @endif

                    <div class="mb-3">
                        <h5>Rated By</h5>
                        <p>{{ $rating->user->name }}</p>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">
                            Created: {{ $rating->created_at->format('M d, Y') }}
                            @if($rating->updated_at != $rating->created_at)
                                • Updated: {{ $rating->updated_at->format('M d, Y') }}
                            @endif
                        </small>
                    </div>

                    @auth
                        @if($rating->user_id === auth()->id())
                            <div class="mt-4">
                                <button class="btn btn-danger"
                                    onclick="deleteRating({{ $rating->id }})">
                                    <i class="fas fa-trash me-2"></i>Delete Rating
                                </button>
                            </div>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </div>
</div>

@auth
<script>
function deleteRating(id) {
    if (!confirm('Are you sure you want to delete this rating?')) {
        return;
    }

    fetch(`/ratings/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '/ratings';
        }
    });
}
</script>
@endauth
@endsection
