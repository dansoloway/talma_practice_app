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
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Poppins Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        /* Ensure Poppins is used everywhere on admin pages */
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50/30 to-purple-50/30 min-h-screen">
    <header class="bg-white/90 backdrop-blur-sm border-b border-gray-200/60 shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.analytics') }}" class="flex items-center gap-3 hover:opacity-80 transition-opacity duration-200" aria-label="TALMA Practice Pal admin home">
                    <img src="{{ asset('logo.svg') }}" alt="TALMA Practice Pal" class="h-9 w-auto">
                    <span class="text-gray-700 font-semibold text-lg">Admin</span>
                </a>
            </div>
            <div class="flex items-center gap-4 flex-wrap">
                <!-- Analytics Dropdown -->
                <div class="relative group">
                    <a href="#" class="flex items-center gap-1.5 px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">
                        Analytics <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-hover:rotate-180"></i>
                    </a>
                    <div class="absolute top-full left-0 mt-1 w-48 bg-white rounded-xl border border-gray-200/60 shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <a href="{{ route('admin.analytics') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-t-xl transition-colors duration-200">Dashboard</a>
                        <a href="{{ route('admin.session-length') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">Session Length</a>
                        <a href="{{ route('admin.openai-usage') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-b-xl transition-colors duration-200">OpenAI Usage & Cost</a>
                    </div>
                </div>
                
                <!-- Lessons Dropdown -->
                <div class="relative group">
                    <a href="#" class="flex items-center gap-1.5 px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">
                        Lessons <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-hover:rotate-180"></i>
                    </a>
                    <div class="absolute top-full left-0 mt-1 w-48 bg-white rounded-xl border border-gray-200/60 shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <a href="{{ route('admin.lessons.index') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-t-xl transition-colors duration-200">All Lessons</a>
                        <a href="{{ route('admin.lesson-tracker') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">Lesson Tracker</a>
                        <a href="{{ route('admin.lessons.archived') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">Archived</a>
                        <a href="{{ route('admin.grammar-concepts.index') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-b-xl transition-colors duration-200">Grammar Sets</a>
                    </div>
                </div>
                
                @if(session('admin_user_role') === 'admin')
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">Users</a>
                @endif
                
                <a href="{{ route('admin.openai-usage') }}" title="View AI Cost Dashboard" class="px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">
                    <i class="fas fa-dollar-sign"></i> AI Costs
                </a>
                
                <a href="{{ route('lessons.index') }}" class="px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">Student View</a>
                <span class="px-3 py-2 text-gray-600 text-sm border-l border-gray-200/60 ml-2 pl-4">{{ session('admin_user_name') }} ({{ ucfirst(session('admin_user_role')) }})</span>
                <a href="#" onclick="logout()" class="px-3 py-2 text-gray-700 hover:text-red-600 font-medium rounded-lg hover:bg-red-50 transition-all duration-200">Logout</a>
            </div>
        </nav>
    </header>

    <main class="min-h-screen py-6">
        <div class="container mx-auto px-4">
            @if(session('success'))
                <div class="bg-green-50/90 backdrop-blur-sm border border-green-200 text-green-800 p-4 rounded-xl shadow-sm mb-6">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle text-green-600"></i>
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50/90 backdrop-blur-sm border border-red-200 text-red-800 p-4 rounded-xl shadow-sm mb-6">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-exclamation-circle text-red-600"></i>
                        <p class="font-medium">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50/90 backdrop-blur-sm border border-red-200 text-red-800 p-4 rounded-xl shadow-sm mb-6">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-circle text-red-600 mt-0.5"></i>
                        <div>
                            <p class="font-semibold mb-2">Please fix the following errors:</p>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
        </div>

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

