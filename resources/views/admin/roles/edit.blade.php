@extends('jav::layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-3"><i class="fas fa-edit me-2"></i>Edit Role: {{ $role->name }}</h1>
            @if(in_array($role->slug, ['admin', 'moderator', 'user']))
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>This is a core system role. Be careful when modifying its permissions.
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-shield-alt me-2"></i>Role Information
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $role->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug" name="slug" value="{{ old('slug', $role->slug) }}" required {{ in_array($role->slug, ['admin', 'moderator', 'user']) ? 'readonly' : '' }}>
                        @error('slug')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if(in_array($role->slug, ['admin', 'moderator', 'user']))
                            <small class="text-muted">Core role slug cannot be changed</small>
                        @endif
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $role->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Assign Permissions</label>
                    @foreach($permissions as $category => $perms)
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong>{{ ucfirst($category) }} Permissions</strong>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($perms as $permission)
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm{{ $permission->id }}" {{ in_array($permission->id, old('permissions', $role->permissions->pluck('id')->toArray())) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="perm{{ $permission->id }}">
                                                    {{ $permission->name }}
                                                    <small class="text-muted d-block">{{ $permission->description }}</small>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i>Update Role
                    </button>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
