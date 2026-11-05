<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#6b2bda">
    <title>@yield('title', 'Admin Dashboard') · E-Novaz Admin</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
            background: #f9fafb;
        }
        
        .admin-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #6b2bda 0%, #5a1ec7 100%);
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }
        
        .admin-sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .admin-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            font-size: 20px;
            font-weight: 700;
        }
        
        .admin-brand img {
            width: 36px;
            height: 36px;
            border-radius: 8px;
        }
        
        .admin-nav {
            padding: 16px 0;
        }
        
        .admin-nav-section {
            margin-bottom: 24px;
        }
        
        .admin-nav-title {
            padding: 0 20px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255,255,255,0.6);
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            transition: all 0.2s;
            position: relative;
        }
        
        .admin-nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .admin-nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
            font-weight: 600;
        }
        
        .admin-nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: white;
            border-radius: 0 4px 4px 0;
        }
        
        .admin-nav-icon {
            width: 20px;
            height: 20px;
            stroke-width: 2;
        }
        
        .admin-main {
            flex: 1;
            margin-left: 260px;
        }
        
        .admin-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .admin-header-left h1 {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }
        
        .admin-header-breadcrumb {
            color: #6b7280;
            font-size: 14px;
            margin-top: 4px;
        }
        
        .admin-header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .admin-user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-radius: 8px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        
        .admin-user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6b2bda, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .admin-user-info {
            text-align: right;
        }
        
        .admin-user-name {
            font-weight: 600;
            color: #111827;
            font-size: 14px;
        }
        
        .admin-user-role {
            font-size: 12px;
            color: #6b7280;
        }
        
        .admin-content {
            padding: 32px;
            max-width: 1400px;
        }
        
        .admin-logout-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        
        .admin-logout-btn:hover {
            background: #dc2626;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 0;
                transform: translateX(-100%);
            }
            .admin-main {
                margin-left: 0;
            }
            .admin-content {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <a href="{{ route('admin.dashboard') }}" class="admin-brand">
                <img src="{{ asset('logo.png') }}" alt="E-Novaz">
                <span>E-Novaz Admin</span>
            </a>
        </div>
        
        <nav class="admin-nav">
            <!-- Dashboard Section -->
            <div class="admin-nav-section">
                <div class="admin-nav-title">Main Menu</div>
                <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <svg class="admin-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                
                <a href="{{ route('admin.users.index') }}" class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg class="admin-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>User Management</span>
                </a>
            </div>
            
            <!-- Store Section (Existing) -->
            @if(auth()->user()->role === 'admin' || in_array(auth()->user()->role ?? '', ['owner','support']))
            <div class="admin-nav-section">
                <div class="admin-nav-title">E-Commerce</div>
                <a href="{{ route('admin.index') }}" class="admin-nav-link {{ request()->routeIs('admin.index') ? 'active' : '' }}">
                    <svg class="admin-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span>Store Admin</span>
                </a>
                
                <a href="{{ route('admin.products') }}" class="admin-nav-link {{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                    <svg class="admin-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>Products</span>
                </a>
                
                <a href="{{ route('admin.orders') }}" class="admin-nav-link {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                    <svg class="admin-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>Orders</span>
                </a>
            </div>
            @endif
            
            <!-- Settings Section -->
            <div class="admin-nav-section">
                <div class="admin-nav-title">Account</div>
                <a href="{{ route('profile') }}" class="admin-nav-link">
                    <svg class="admin-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>My Profile</span>
                </a>
                
                <a href="{{ route('shop') }}" class="admin-nav-link">
                    <svg class="admin-nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Back to Shop</span>
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="admin-main">
        <!-- Header -->
        <header class="admin-header">
            <div class="admin-header-left">
                <h1>@yield('page-title', 'Dashboard')</h1>
                @hasSection('breadcrumb')
                <div class="admin-header-breadcrumb">@yield('breadcrumb')</div>
                @endif
            </div>
            
            <div class="admin-header-right">
                <div class="admin-user-menu">
                    <div class="admin-user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                    <div class="admin-user-info">
                        <div class="admin-user-name">{{ auth()->user()->name }}</div>
                        <div class="admin-user-role">{{ ucfirst(auth()->user()->role) }}</div>
                    </div>
                </div>
                
                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="admin-logout-btn">Logout</button>
                </form>
            </div>
        </header>
        
        <!-- Content -->
        <div class="admin-content">
            @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                {{ session('error') }}
            </div>
            @endif

            @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

@stack('scripts')
</body>
</html>
