@extends('jav::layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-3"><i class="fas fa-shield-alt me-2"></i>Role Management</h1>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i>All Roles</span>
            @if(auth()->user()->hasPermission('create-roles'))
                <a href="{{ route('admin.roles.create') }}" class="btn btn-light btn-sm">
                    <i class="fas fa-plus me-1"></i>Add New Role
                </a>
            @endif
        </div>
        <div class="card-body">
            <!-- Search Form -->
            <form method="GET" action="{{ route('admin.roles.index') }}" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Search by name, slug, or description" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="per_page" class="form-select">
                            <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15 per page</option>
                            <option value="30" {{ request('per_page') == 30 ? 'selected' : '' }}>30 per page</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 per page</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>

            <!-- Roles Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td>{{ $role->id }}</td>
                                <td><strong>{{ $role->name }}</strong></td>
                                <td><code>{{ $role->slug }}</code></td>
                                <td>{{ Str::limit($role->description, 50) }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $role->permissions->count() }} permissions</span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        @if(auth()->user()->hasPermission('view-roles'))
                                            <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endif
                                        @if(auth()->user()->hasPermission('edit-roles'))
                                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if(auth()->user()->hasPermission('delete-roles') && !in_array($role->slug, ['admin', 'moderator', 'user']))
                                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this role?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No roles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $roles->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
