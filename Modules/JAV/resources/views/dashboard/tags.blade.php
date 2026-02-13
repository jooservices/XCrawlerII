@extends('jav::layouts.dashboard')

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Tags</h2>
            </div>
        </div>

        <!-- Search Bar moved to navbar -->

        <!-- Tags List -->
        <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-4" id="lazy-container">
            @forelse($tags as $tag)
                @include('jav::dashboard.partials.tag_card', ['tag' => $tag])
            @empty
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        No tags found.
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Lazy Loading Sentinel -->
        <div id="sentinel" data-next-url="{{ $tags->withQueryString()->nextPageUrl() }}"></div>
        <div id="loading-spinner" class="text-center my-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
@endsection