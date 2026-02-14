@extends('jav::layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-sliders-h me-2"></i>User Preferences</h2>
            <p class="text-muted mb-0">Control dashboard display, text style, and saved behavior.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('jav.preferences.save') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Text Preference</label>
                        <select name="text_preference" class="form-select">
                            <option value="detailed" {{ ($preferences['text_preference'] ?? 'detailed') === 'detailed' ? 'selected' : '' }}>
                                Detailed
                            </option>
                            <option value="concise" {{ ($preferences['text_preference'] ?? 'detailed') === 'concise' ? 'selected' : '' }}>
                                Concise
                            </option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Language Preference</label>
                        <select name="language" class="form-select">
                            <option value="en" {{ ($preferences['language'] ?? 'en') === 'en' ? 'selected' : '' }}>English UI</option>
                            <option value="jp" {{ ($preferences['language'] ?? 'en') === 'jp' ? 'selected' : '' }}>Japanese-first metadata</option>
                        </select>
                    </div>
                </div>

                <hr>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="hide_actors" name="hide_actors" value="1"
                        {{ !empty($preferences['hide_actors']) ? 'checked' : '' }}>
                    <label class="form-check-label" for="hide_actors">Hide actor chips on movie cards</label>
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="hide_tags" name="hide_tags" value="1"
                        {{ !empty($preferences['hide_tags']) ? 'checked' : '' }}>
                    <label class="form-check-label" for="hide_tags">Hide tag chips on movie cards</label>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="compact_mode" name="compact_mode" value="1"
                        {{ !empty($preferences['compact_mode']) ? 'checked' : '' }}>
                    <label class="form-check-label" for="compact_mode">Compact mode (smaller cards)</label>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Save Preferences
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
