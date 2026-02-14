@extends('jav::layouts.dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-4">
                <img src="{{ $actor->cover }}" class="img-fluid rounded shadow" alt="{{ $actor->name }}"
                    onerror="this.src='https://placehold.co/400x600?text=No+Image'">
            </div>
            <div class="col-md-8">
                <h2 class="mb-2">{{ $actor->name }}</h2>
                <div class="mb-3">
                    <span class="badge bg-secondary">{{ $actor->javs_count ?? 0 }} JAVs</span>
                    @if(!empty($primarySource))
                        <span class="badge bg-dark">{{ strtoupper($primarySource) }} Primary</span>
                    @endif
                    @if(!empty($primarySyncedAt))
                        <span class="badge bg-info text-dark">Synced: {{ $primarySyncedAt->format('Y-m-d H:i') }}</span>
                    @endif
                </div>

                <div class="mb-3">
                    <a href="{{ route('jav.blade.dashboard', ['actor' => $actor->name]) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-film me-1"></i> Show All JAVs
                    </a>
                    <a href="{{ route('jav.blade.actors') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Actors
                    </a>
                </div>

                <h5>Bio Profile</h5>
                @if(!empty($bioProfile))
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered bg-white">
                            <tbody>
                                @foreach($bioProfile as $label => $value)
                                    <tr>
                                        <th style="width: 220px;">{{ $label }}</th>
                                        <td>{{ $value }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-warning mb-0">
                        No profile data synced yet.
                    </div>
                @endif
            </div>
        </div>

        <hr class="my-4">

        <div class="row mb-3">
            <div class="col-12">
                <h4>JAVs</h4>
            </div>
        </div>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4">
            @forelse($movies as $item)
                @include('jav::dashboard.partials.movie_card', ['item' => $item])
            @empty
                <div class="col-12">
                    <div class="alert alert-warning text-center mb-0">
                        No JAVs found for this actor.
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $movies->links() }}
        </div>
    </div>
@endsection
