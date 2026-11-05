@extends('layouts.admin')

@section('page-title', 'User Details')
@section('breadcrumb', 'View and manage user: ' . $user->name)

@section('content')
<!-- Header Action -->
<div class="flex justify-end items-center mb-6">
    <a href="{{ route('admin.users.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition">
        ← Back to Users
    </a>
</div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ session('error') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Info Card -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">User Information</h2>
                
                <div class="space-y-4">
                    <div class="flex">
                        <span class="font-semibold text-gray-600 w-40">ID:</span>
                        <span class="text-gray-900">#{{ $user->id }}</span>
                    </div>
                    
                    <div class="flex">
                        <span class="font-semibold text-gray-600 w-40">Name:</span>
                        <span class="text-gray-900">{{ $user->name }}</span>
                    </div>
                    
                    <div class="flex">
                        <span class="font-semibold text-gray-600 w-40">Email:</span>
                        <span class="text-gray-900">{{ $user->email }}</span>
                    </div>
                    
                    <div class="flex">
                        <span class="font-semibold text-gray-600 w-40">Role:</span>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </div>
                    
                    <div class="flex">
                        <span class="font-semibold text-gray-600 w-40">Status:</span>
                        @if($user->isLocked())
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                🔒 Locked until {{ $user->locked_until->format('Y-m-d H:i:s') }}
                            </span>
                        @elseif($user->is_active)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                ✓ Active
                            </span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                ✗ Inactive
                            </span>
                        @endif
                    </div>
                    
                    <div class="flex">
                        <span class="font-semibold text-gray-600 w-40">Failed Attempts:</span>
                        <span class="px-2 py-1 rounded {{ $user->failed_login_attempts >= 3 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $user->failed_login_attempts }}/5
                        </span>
                    </div>
                    
                    <div class="flex">
                        <span class="font-semibold text-gray-600 w-40">Registered:</span>
                        <span class="text-gray-900">{{ $user->created_at->format('Y-m-d H:i:s') }}</span>
                    </div>
                    
                    <div class="flex">
                        <span class="font-semibold text-gray-600 w-40">Last Login:</span>
                        <span class="text-gray-900">{{ $user->last_login_at?->format('Y-m-d H:i:s') ?? 'Never' }}</span>
                    </div>
                    
                    <div class="flex">
                        <span class="font-semibold text-gray-600 w-40">Last Updated:</span>
                        <span class="text-gray-900">{{ $user->updated_at->format('Y-m-d H:i:s') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions Card -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Actions</h2>
                
                <div class="space-y-3">
                    <!-- Edit User -->
                    <a href="{{ route('admin.users.edit', $user) }}" 
                        class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center px-4 py-2 rounded-lg">
                        ✏️ Edit User
                    </a>
                    
                    <!-- Toggle Active Status -->
                    @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.toggle-active', $user) }}">
                        @csrf
                        <button type="submit" class="block w-full {{ $user->is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white text-center px-4 py-2 rounded-lg">
                            {{ $user->is_active ? '🔒 Deactivate User' : '✓ Activate User' }}
                        </button>
                    </form>
                    @endif
                    
                    <!-- Unlock Account -->
                    @if($user->isLocked())
                    <form method="POST" action="{{ route('admin.users.unlock', $user) }}">
                        @csrf
                        <button type="submit" class="block w-full bg-orange-600 hover:bg-orange-700 text-white text-center px-4 py-2 rounded-lg">
                            🔓 Unlock Account
                        </button>
                    </form>
                    @endif
                    
                    <!-- Reset Password -->
                    <button onclick="toggleResetPassword()" class="block w-full bg-purple-600 hover:bg-purple-700 text-white text-center px-4 py-2 rounded-lg">
                        🔑 Reset Password
                    </button>
                    
                    <!-- Delete User -->
                    @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.delete', $user) }}" 
                        onsubmit="return confirm('PERHATIAN!\n\nAnda yakin ingin menghapus user ini?\nTindakan ini tidak dapat dibatalkan!')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="block w-full bg-red-600 hover:bg-red-700 text-white text-center px-4 py-2 rounded-lg">
                            🗑️ Delete User
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            <!-- Reset Password Form (Hidden by default) -->
            <div id="resetPasswordForm" class="bg-white rounded-lg shadow-md p-6 mt-6 hidden">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Reset Password</h2>
                
                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">New Password</label>
                        <input type="password" name="new_password" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2"
                            placeholder="Minimum 8 characters">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 font-semibold mb-2">Confirm Password</label>
                        <input type="password" name="new_password_confirmation" required
                            class="w-full border border-gray-300 rounded-lg px-4 py-2"
                            placeholder="Re-type password">
                    </div>
                    
                    <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            <strong>Password Requirements:</strong><br>
                            • Minimum 8 characters<br>
                            • Uppercase & lowercase letters<br>
                            • Numbers<br>
                            • Special characters (@$!%*?&)
                        </p>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
                            Reset Password
                        </button>
                        <button type="button" onclick="toggleResetPassword()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
function toggleResetPassword() {
    const form = document.getElementById('resetPasswordForm');
    form.classList.toggle('hidden');
}
</script>
@endsection
