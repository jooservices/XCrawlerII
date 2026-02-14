@extends('jav::layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-3"><i class="fas fa-user me-2"></i>User Details</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-info-circle me-2"></i>User Information
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">ID:</th>
                            <td>{{ $user->id }}</td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>{{ $user->name }}</td>
                        </tr>
                        <tr>
                            <th>Username:</th>
                            <td>{{ $user->username }}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <th>Email Verified:</th>
                            <td>
                                @if($user->email_verified_at)
                                    <span class="badge bg-success">Yes</span>
                                    <small class="text-muted">({{ $user->email_verified_at->format('Y-m-d H:i') }})</small>
                                @else
                                    <span class="badge bg-warning">No</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Created:</th>
                            <td>{{ $user->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td>{{ $user->updated_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-shield-alt me-2"></i>Roles & Permissions
                </div>
                <div class="card-body">
                    <h6 class="mb-3">Assigned Roles:</h6>
                    @if($user->roles->count() > 0)
                        <div class="mb-3">
                            @foreach($user->roles as $role)
                                <span class="badge bg-info me-2 mb-2">{{ $role->name }}</span>
                            @endforeach
                        </div>

                        <h6 class="mb-3 mt-4">Permissions (via roles):</h6>
                        <div class="accordion" id="permissionsAccordion">
                            @foreach($user->roles as $index => $role)
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $role->id }}">
                                            {{ $role->name }} ({{ $role->permissions->count() }} permissions)
                                        </button>
                                    </h2>
                                    <div id="collapse{{ $role->id }}" class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" data-bs-parent="#permissionsAccordion">
                                        <div class="accordion-body">
                                            @if($role->permissions->count() > 0)
                                                <ul class="list-unstyled">
                                                    @foreach($role->permissions as $permission)
                                                        <li><i class="fas fa-check text-success me-2"></i>{{ $permission->name }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-muted">No permissions assigned to this role.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No roles assigned to this user.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        @if(auth()->user()->hasPermission('edit-users'))
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-warning">
                <i class="fas fa-edit me-1"></i>Edit User
            </a>
        @endif
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to List
        </a>
    </div>
</div>
@endsection
