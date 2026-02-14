@extends('jav::layouts.dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-history"></i> My History</h2>
                <p class="text-muted">Track your viewed and downloaded movies</p>
            </div>
        </div>

        @if($history->isEmpty())
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> You haven't viewed or downloaded any movies yet.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Movie</th>
                            <th>Code</th>
                            <th>Action</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $record)
                            <tr onclick="window.location='{{ route('jav.blade.movies.show', $record->jav) }}'" style="cursor: pointer;">
                                <td>
                                    <img src="{{ $record->jav->cover }}" alt="{{ $record->jav->formatted_code }}"
                                        class="img-thumbnail me-2" style="width: 60px;"
                                        onerror="this.src='https://placehold.co/60x80?text=No+Image'">
                                    {{ Str::limit($record->jav->title, 50) }}
                                </td>
                                <td><strong class="text-primary">{{ $record->jav->formatted_code }}</strong></td>
                                <td>
                                    @if($record->action === 'view')
                                        <span class="badge bg-info"><i class="fas fa-eye"></i> Viewed</span>
                                    @else
                                        <span class="badge bg-success"><i class="fas fa-download"></i> Downloaded</span>
                                    @endif
                                </td>
                                <td>{{ $record->updated_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $history->links() }}
            </div>
        @endif
    </div>
@endsection