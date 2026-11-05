@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Edit User</h1>
            <p class="text-gray-600 mt-2">Update user information</p>
        </div>
        <a href="{{ route('admin.users.show', $user) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
            ← Back
        </a>
    </div>

    @if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')

                <!-- Name -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Email -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Role -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Role</label>
                    <select name="role" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>User</option>
                        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Admin dapat mengakses dashboard dan manage users</p>
                </div>

                <!-- Active Status -->
                <div class="mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" 
                            {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-gray-700 font-semibold">Account Active</span>
                    </label>
                    <p class="text-sm text-gray-500 mt-1">Nonaktifkan untuk mencegah user login</p>
                </div>

                <!-- User Info (Read-only) -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="font-semibold text-gray-700 mb-2">Account Information</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex">
                            <span class="text-gray-600 w-32">User ID:</span>
                            <span class="text-gray-900">#{{ $user->id }}</span>
                        </div>
                        <div class="flex">
                            <span class="text-gray-600 w-32">Registered:</span>
                            <span class="text-gray-900">{{ $user->created_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="flex">
                            <span class="text-gray-600 w-32">Last Login:</span>
                            <span class="text-gray-900">{{ $user->last_login_at?->format('Y-m-d H:i') ?? 'Never' }}</span>
                        </div>
                        <div class="flex">
                            <span class="text-gray-600 w-32">Failed Attempts:</span>
                            <span class="text-gray-900">{{ $user->failed_login_attempts }}/5</span>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                        💾 Save Changes
                    </button>
                    <a href="{{ route('admin.users.show', $user) }}" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold text-center">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
