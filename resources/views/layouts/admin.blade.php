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
                <a href="{{ route('admin.analytics') }}">Analytics</a>
                <a href="{{ route('admin.session-length') }}">Session Length</a>
                <a href="{{ route('admin.lesson-tracker') }}">Lesson Tracker</a>
                <a href="{{ route('admin.lessons.index') }}">Lessons</a>
                <a href="{{ route('admin.lessons.archived') }}">Archived</a>
                <a href="{{ route('lessons.index') }}">Student View</a>
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

