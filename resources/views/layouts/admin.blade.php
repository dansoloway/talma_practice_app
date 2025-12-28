<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - TALMA Practice Pal</title>
    
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="admin-body">
    <header class="admin-header">
        <nav class="nav">
            <div class="nav-brand">
                <a href="{{ route('admin.analytics') }}" class="nav-logo-link" aria-label="TALMA Practice Pal admin home">
                    <img src="{{ asset('logo.svg') }}" alt="TALMA Practice Pal" class="nav-logo">
                    <span class="nav-logo-text">Admin</span>
                </a>
            </div>
            <div class="nav-links">
                <!-- Analytics Dropdown -->
                <div class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">
                        Analytics <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="nav-dropdown-menu">
                        <a href="{{ route('admin.analytics') }}">Dashboard</a>
                        <a href="{{ route('admin.session-length') }}">Session Length</a>
                    </div>
                </div>
                
                <!-- Lessons Dropdown -->
                <div class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">
                        Lessons <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="nav-dropdown-menu">
                        <a href="{{ route('admin.lessons.index') }}">All Lessons</a>
                        <a href="{{ route('admin.lesson-tracker') }}">Lesson Tracker</a>
                        <a href="{{ route('admin.lessons.archived') }}">Archived</a>
                    </div>
                </div>
                
                @if(session('admin_user_role') === 'admin')
                    <a href="{{ route('admin.users.index') }}">Users</a>
                @endif
                
                <a href="{{ route('lessons.index') }}">Student View</a>
                <span class="nav-user">{{ session('admin_user_name') }} ({{ ucfirst(session('admin_user_role')) }})</span>
                <a href="#" onclick="logout()" class="logout-link">Logout</a>
            </div>
        </nav>
    </header>

    <main class="admin-content">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
    
    <style>
    /* Navigation Dropdown Styles */
    .nav-dropdown {
        position: relative;
        display: inline-block;
    }
    
    .nav-dropdown-toggle {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        color: var(--color-white);
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition-fast);
        padding: 0.5rem 0;
    }
    
    .nav-dropdown-toggle:hover {
        text-decoration: underline;
    }
    
    .nav-dropdown-toggle i {
        font-size: 0.7rem;
        transition: transform 0.2s;
        margin-left: 0.25rem;
    }
    
    .nav-dropdown:hover .nav-dropdown-toggle i {
        transform: rotate(180deg);
    }
    
    .nav-dropdown-menu {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        margin-top: 0.25rem;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        min-width: 180px;
        z-index: 1000;
        padding: 0.25rem 0;
    }
    
    /* Show menu when hovering over dropdown container OR the menu itself */
    .nav-dropdown:hover .nav-dropdown-menu,
    .nav-dropdown-menu:hover {
        display: block;
    }
    
    /* Add invisible bridge to prevent gap issues */
    .nav-dropdown::before {
        content: '';
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        height: 0.5rem;
        z-index: 999;
    }
    
    .nav-dropdown-menu a {
        display: block;
        padding: 0.75rem 1rem;
        color: var(--color-text);
        text-decoration: none;
        transition: var(--transition-fast);
        font-weight: 500;
    }
    
    .nav-dropdown-menu a:hover {
        background-color: var(--color-primary-bg);
        color: var(--color-primary);
    }
    
    .nav-dropdown-menu a.active {
        background-color: var(--color-primary);
        color: white;
    }
    
    /* Better spacing for nav items */
    .admin-header .nav-links {
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    /* Separate user info and logout */
    .nav-user {
        margin-left: 0.5rem;
        padding-left: 1rem;
        border-left: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .logout-link {
        margin-left: 0.5rem;
    }
    </style>
    
    <script>
    function logout() {
        if (confirm('Are you sure you want to logout?')) {
            fetch('{{ route('admin.logout') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                },
            }).then(() => {
                window.location.href = '{{ route('lessons.index') }}';
            });
        }
    }
    </script>
</body>
</html>

