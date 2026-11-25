@extends('layouts.admin')

@section('content')
<div class="container-fluid my-2">
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="mb-1">User Management</h4>
            <p class="text-muted mb-0">Manage all users and their roles</p>
        </div>
    
        <div class="d-flex gap-2 align-self-start align-self-sm-center">
            <a href="{{ route('user.export') }}" class="btn btn-success">
                <i class="ti ti-download me-1"></i>
                Export
            </a>
    
            <a href="{{ route('user.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>
                Add User
            </a>
        </div>
    </div>

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

    <div class="card">
        <div class="card-body">
            <!-- FILTERS -->
            <form method="GET" action="{{ route('user.index') }}" class="mb-4">
                <div class="filters-responsive">
                    <div class="filter-item filter-search">
                        <label class="form-label small text-muted mb-1">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
                    </div>

                    <div class="filter-item filter-status">
                        <label class="form-label small text-muted mb-1">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="filter-item filter-role">
                        <label class="form-label small text-muted mb-1">Role</label>
                        <select name="role_id" class="form-select">
                            <option value="">All Roles</option>
                            @foreach($roles ?? [] as $role)
                                <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-item filter-buttons">
                        <label class="form-label small text-muted mb-1 d-none d-md-block">&nbsp;</label>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-1">
                                <i class="ti ti-filter"></i> Filter
                            </button>
                            <a href="{{ route('user.index') }}" class="btn btn-secondary d-flex align-items-center justify-content-center gap-1">
                                <i class="ti ti-refresh"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-custom table-centered table-hover w-100 mb-0">
                    <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                        <tr class="text-uppercase fs-xxs">
                            <th>#</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $key => $user)
                            <tr>
                                <td class="align-middle">{{ $users->firstItem() + $key }}</td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-sm">
                                            @if($user->avatar)
                                                <img src="{{ asset('storage/avatars/' . $user->avatar) }}" 
                                                     class="rounded-circle" width="40" height="40" alt="{{ $user->name }}">
                                            @else
                                                <div class="avatar-placeholder rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                                     style="width:40px;height:40px;font-weight:bold;font-size:16px;">
                                                    {{ $user->initials ?? substr($user->name, 0, 2) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <h5 class="mb-0 fs-6">{{ $user->name }}</h5>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">{{ $user->email }}</td>
                                <td class="align-middle">{{ $user->phone ?? '-' }}</td>
                                <td class="align-middle">
                                    @php
                                        // Handle both relationship and string role
                                        $roleData = $user->role;
                                        $roleName = 'No Role';
                                        $roleColor = 'secondary';
                                        $roleIcon = 'ti-user';
                                        
                                        if (is_object($roleData) && isset($roleData->display_name)) {
                                            // Relationship object
                                            $roleName = $roleData->display_name;
                                            $roleColor = $roleData->color ?? 'secondary';
                                            $roleIcon = $roleData->icon ?? 'ti-user';
                                        } elseif (is_string($roleData)) {
                                            // String from old column
                                            $roleName = ucfirst(str_replace('_', ' ', $roleData));
                                            $colors = [
                                                'admin' => 'danger',
                                                'web_designer' => 'info',
                                                'order_manager' => 'warning',
                                                'manager' => 'success',
                                                'user' => 'secondary',
                                            ];
                                            $roleColor = $colors[$roleData] ?? 'secondary';
                                        } elseif ($user->role_id) {
                                            // Try to load from relationship
                                            $role = $user->role()->first();
                                            if ($role) {
                                                $roleName = $role->display_name;
                                                $roleColor = $role->color ?? 'secondary';
                                                $roleIcon = $role->icon ?? 'ti-user';
                                            }
                                        }
                                    @endphp
                                    <span class="badge bg-{{ $roleColor }} px-3 py-2 rounded-pill">
                                        <i class="ti {{ $roleIcon }} me-1"></i>
                                        {{ $roleName }}
                                    </span>
                                </td>
                                <td class="align-middle">
                                    @if($user->status == 1)
                                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fs-xxs">
                                            <i class="ti ti-circle-check me-1"></i> Active
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fs-xxs">
                                            <i class="ti ti-circle-x me-1"></i> Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle">{{ $user->created_at->format('d M Y') }}</td>
                                <td class="align-middle">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('user.edit', $user) }}" class="btn btn-light btn-icon btn-sm rounded-circle" title="Edit">
                                            <i class="ti ti-edit fs-5"></i>
                                        </a>
                                        @if($user->id != auth()->id())
                                        <form action="{{ route('user.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete this user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-light btn-icon btn-sm rounded-circle" title="Delete">
                                                <i class="ti ti-trash fs-5 text-danger"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ti ti-users fs-1 text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No users found</p>
                                        <a href="{{ route('user.create') }}" class="btn btn-sm btn-primary mt-3">
                                            <i class="ti ti-plus me-1"></i> Add your first user
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION - FIXED -->
            @if($users->hasPages())
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-2 border-top">
                    <div class="text-muted small mb-3 mb-md-0">
                        Showing
                        <span class="fw-semibold">{{ $users->firstItem() }}</span>
                        to
                        <span class="fw-semibold">{{ $users->lastItem() }}</span>
                        of
                        <span class="fw-semibold">{{ $users->total() }}</span>
                        entries
                    </div>
                    <div>
                        {{ $users->appends(request()->except('page'))->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

