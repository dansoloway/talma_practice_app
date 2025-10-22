<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'WeSpeak - Sentence Speaking')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="nav-brand">
                <a href="{{ route('lessons.index') }}">WeSpeak</a>
            </div>
            <div class="nav-links">
                <a href="{{ route('lessons.index') }}">Lessons</a>
                <a href="{{ route('admin.dashboard') }}">Admin</a>
            </div>
        </nav>
    </header>

    <main class="main-content">
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

        @yield('content')
    </main>

    <footer class="footer">
        <p>&copy; {{ date('Y') }} WeSpeak. All rights reserved.</p>
    </footer>

    @stack('scripts')
</body>
</html>

