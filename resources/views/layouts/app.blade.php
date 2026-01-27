<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TALMA Practice Pal')</title>
    
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ time() }}">
    @stack('styles')
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 min-h-screen">
    <header class="bg-white/90 backdrop-blur-sm border-b border-gray-200/60 shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <a href="{{ route('lessons.index') }}" class="flex items-center gap-3 hover:opacity-80 transition-opacity duration-200" aria-label="TALMA Practice Pal home">
                    <img src="{{ asset('logo.svg') }}" alt="TALMA Practice Pal" class="h-9 w-auto">
                </a>
            </div>
            <button class="md:hidden p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-all duration-200" id="nav-toggle" aria-label="Toggle navigation">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="hidden md:flex items-center gap-6" id="nav-links">
                <a href="{{ route('lessons.index') }}" class="text-gray-700 hover:text-blue-600 font-medium transition-colors duration-200 px-3 py-2 rounded-lg hover:bg-blue-50">Lessons</a>
                <a href="{{ route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium transition-colors duration-200 px-3 py-2 rounded-lg hover:bg-blue-50">Admin</a>
            </div>
        </nav>
        <!-- Mobile menu -->
        <div class="hidden md:hidden border-t border-gray-200/60 bg-white/95 backdrop-blur-sm" id="mobile-nav">
            <div class="container mx-auto px-4 py-4 flex flex-col gap-2">
                <a href="{{ route('lessons.index') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">Lessons</a>
                <a href="{{ route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">Admin</a>
            </div>
        </div>
    </header>
    
    <script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const navToggle = document.getElementById('nav-toggle');
        const mobileNav = document.getElementById('mobile-nav');
        
        if (navToggle && mobileNav) {
            navToggle.addEventListener('click', function() {
                mobileNav.classList.toggle('hidden');
                const icon = this.querySelector('i');
                if (!mobileNav.classList.contains('hidden')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        }
    });
    </script>

    <main class="min-h-screen">
        @if(session('success'))
            <div class="container mx-auto px-4 pt-6">
                <div class="bg-green-50/90 backdrop-blur-sm border border-green-200 text-green-800 p-4 rounded-xl shadow-sm mb-6">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle text-green-600"></i>
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="container mx-auto px-4 pt-6">
                <div class="bg-red-50/90 backdrop-blur-sm border border-red-200 text-red-800 p-4 rounded-xl shadow-sm mb-6">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-circle text-red-600"></i>
                        <p class="font-medium">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white/80 backdrop-blur-sm border-t border-gray-200/60 mt-12 py-6">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-600 text-sm">&copy; {{ date('Y') }} TALMA. All rights reserved.</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

