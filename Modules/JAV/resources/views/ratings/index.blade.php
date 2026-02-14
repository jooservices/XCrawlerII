@extends('jav::layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-star me-2"></i>Ratings & Reviews</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @if($ratings->isEmpty())
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-star fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No ratings yet</p>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body">
                        @foreach($ratings as $rating)
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="mb-2">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star {{ $i <= $rating->rating ? 'text-warning' : 'text-muted' }}"></i>
                                            @endfor
                                            <span class="ms-2 text-muted">{{ $rating->rating }}/5</span>
                                        </div>
                                        @if($rating->review)
                                            <p class="mb-2">{{ $rating->review }}</p>
                                        @endif
                                        <small class="text-muted">
                                            by {{ $rating->user->name }} • {{ $rating->created_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    @auth
                                        @if($rating->user_id === auth()->id())
                                            <div>
                                                <button class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteRating({{ $rating->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        @endif
                                    @endauth
                                </div>
                            </div>
                        @endforeach

                        <div class="mt-3">
                            {{ $ratings->links() }}
                        </div>
                    </div>
                </div>
            @endif
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
            window.location.reload();
        }
    });
}
</script>
@endauth
@endsection
