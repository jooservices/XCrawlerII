@extends('jav::layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-3"><i class="fas fa-shield-alt me-2"></i>Role Details: {{ $role->name }}</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-info-circle me-2"></i>Role Information
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">ID:</th>
                            <td>{{ $role->id }}</td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td><strong>{{ $role->name }}</strong></td>
                        </tr>
                        <tr>
                            <th>Slug:</th>
                            <td><code>{{ $role->slug }}</code></td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td>{{ $role->description ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Created:</th>
                            <td>{{ $role->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td>{{ $role->updated_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <i class="fas fa-users me-2"></i>Users with this Role ({{ $role->users->count() }})
                </div>
                <div class="card-body">
                    @if($role->users->count() > 0)
                        <ul class="list-group">
                            @foreach($role->users->take(10) as $user)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $user->name }}
                                    <small class="text-muted">{{ $user->email }}</small>
                                </li>
                            @endforeach
                        </ul>
                        @if($role->users->count() > 10)
                            <p class="text-muted mt-2 mb-0">
                                <small>Showing 10 of {{ $role->users->count() }} users</small>
                            </p>
                        @endif
                    @else
                        <p class="text-muted mb-0">No users have this role yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-key me-2"></i>Permissions ({{ $role->permissions->count() }})
                </div>
                <div class="card-body">
                    @if($role->permissions->count() > 0)
                        @php
                            $groupedPermissions = $role->permissions->groupBy(function($permission) {
                                return explode('-', $permission->slug)[0];
                            });
                        @endphp

                        @foreach($groupedPermissions as $category => $perms)
                            <div class="mb-3">
                                <h6 class="text-uppercase text-muted mb-2">{{ ucfirst($category) }}</h6>
                                <ul class="list-unstyled">
                                    @foreach($perms as $permission)
                                        <li class="mb-1">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <strong>{{ $permission->name }}</strong>
                                            @if($permission->description)
                                                <br>
                                                <small class="text-muted ms-4">{{ $permission->description }}</small>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">No permissions assigned to this role.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        @if(auth()->user()->hasPermission('edit-roles'))
            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning">
                <i class="fas fa-edit me-1"></i>Edit Role
            </a>
        @endif
        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to List
        </a>
    </div>
</div>
@endsection
