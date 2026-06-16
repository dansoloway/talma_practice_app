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
    @php
        $org = $currentOrganization ?? null;
        $analyticsUrl = $org ? route('org.admin.analytics', ['organization' => $org->slug]) : route('admin.analytics');
        $coursesUrl = $org ? route('org.admin.courses.index', ['organization' => $org->slug]) : route('admin.courses.index');
    @endphp
    <header class="bg-white/90 backdrop-blur-sm border-b border-gray-200/60 shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="{{ $analyticsUrl }}" class="flex items-center gap-2 hover:opacity-80 transition-opacity duration-200" aria-label="Dashboard">
                    <img src="{{ asset('logo.svg') }}" alt="TALMA Practice Pal" class="h-9 w-auto">
                </a>
                @if($accessibleOrgs->isNotEmpty())
                    <span class="text-gray-400">|</span>
                    <div class="relative group">
                        <button type="button" class="flex items-center gap-1.5 px-2 py-1.5 text-gray-600 hover:text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-50/80 transition-all duration-200">
                            <span>{{ $org ? $org->name : 'Select org' }}</span>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-hover:rotate-180"></i>
                        </button>
                        <div class="absolute top-full left-0 pt-1 -mt-1">
                            <div class="bg-white rounded-xl border border-gray-200/60 shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-1 min-w-[180px]">
                            @foreach($accessibleOrgs as $o)
                                <form method="POST" action="{{ route('admin.org.select.store') }}" class="block">
                                    @csrf
                                    <input type="hidden" name="organization" value="{{ $o->slug }}">
                                    <button type="submit" class="w-full text-left px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 flex items-center justify-between {{ $org && $org->id === $o->id ? 'bg-blue-50/50 font-medium' : '' }}">
                                        <span>{{ $o->name }}</span>
                                        @if($org && $org->id === $o->id)
                                            <i class="fas fa-check text-blue-600 text-sm"></i>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Content Dropdown: Courses, Classes, Lessons -->
                <div class="relative group">
                    <button type="button" class="flex items-center gap-1.5 px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">
                        Courses &amp; Lessons <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-hover:rotate-180"></i>
                    </button>
                    <div class="absolute top-full left-0 pt-1 -mt-1">
                        <div class="w-52 bg-white rounded-xl border border-gray-200/60 shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-1">
                        <a href="{{ $coursesUrl }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">
                            <i class="fas fa-book-open w-5 mr-2 text-gray-400"></i>Courses
                        </a>
                        @if($org)
                            <a href="{{ route('org.admin.classrooms.index', ['organization' => $org->slug]) }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">
                                <i class="fas fa-chalkboard-teacher w-5 mr-2 text-gray-400"></i>Classes
                            </a>
                            <div class="border-t border-gray-100 my-1"></div>
                        @endif
                        <a href="{{ route('admin.lessons.index') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">
                            <i class="fas fa-file-alt w-5 mr-2 text-gray-400"></i>All Lessons
                        </a>
                        <a href="{{ route('admin.lesson-tracker') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">
                            <i class="fas fa-tasks w-5 mr-2 text-gray-400"></i>Lesson Tracker
                        </a>
                        <a href="{{ route('admin.lessons.archived') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">
                            <i class="fas fa-archive w-5 mr-2 text-gray-400"></i>Archived
                        </a>
                        <a href="{{ route('admin.grammar-concepts.index') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 rounded-b-xl">
                            <i class="fas fa-language w-5 mr-2 text-gray-400"></i>Grammar Sets
                        </a>
                        </div>
                    </div>
                </div>

                <!-- Analytics Dropdown -->
                <div class="relative group">
                    <button type="button" class="flex items-center gap-1.5 px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">
                        Analytics <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-hover:rotate-180"></i>
                    </button>
                    <div class="absolute top-full left-0 pt-1 -mt-1">
                        <div class="w-48 bg-white rounded-xl border border-gray-200/60 shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-1">
                        <a href="{{ $analyticsUrl }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-t-xl transition-colors duration-200">
                            <i class="fas fa-chart-line w-5 mr-2 text-gray-400"></i>Dashboard
                        </a>
                        <a href="{{ route('admin.session-length') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">
                            <i class="fas fa-clock w-5 mr-2 text-gray-400"></i>Session Length
                        </a>
                        <a href="{{ route('admin.openai-usage') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 rounded-b-xl">
                            <i class="fas fa-dollar-sign w-5 mr-2 text-gray-400"></i>AI Usage & Cost
                        </a>
                        </div>
                    </div>
                </div>

                @if(auth('admin')->user()?->role === 'admin')
                    <a href="{{ route('admin.organizations.index') }}" class="px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">
                        <i class="fas fa-building mr-1.5 text-gray-400"></i>Organizations
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">
                        <i class="fas fa-users mr-1.5 text-gray-400"></i>Users
                    </a>
                @endif

                <a href="{{ route('lessons.index') }}" class="px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200" title="View as student">
                    <i class="fas fa-graduation-cap mr-1.5 text-gray-400"></i>Student View
                </a>

                <div class="border-l border-gray-200 h-6 mx-1"></div>
                <!-- User menu: click Daniel (Admin) for Logout -->
                <div class="relative group">
                    <button type="button" class="flex items-center gap-1.5 px-3 py-2 text-gray-600 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">
                        <i class="fas fa-user-circle text-gray-400"></i>
                        <span>{{ auth('admin')->user()->name }} ({{ ucfirst(auth('admin')->user()->role) }})</span>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-hover:rotate-180"></i>
                    </button>
                    <div class="absolute top-full right-0 pt-1 -mt-1">
                        <div class="bg-white rounded-xl border border-gray-200/60 shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-1 min-w-[160px]">
                            <a href="#" onclick="logout(); return false;" class="block px-4 py-2.5 text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors duration-200 rounded-lg">
                                <i class="fas fa-sign-out-alt mr-2 text-gray-400"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
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
                    'Accept': 'application/json',
                },
            }).then((res) => res.json().then((data) => {
                window.location.href = data.redirect || '{{ route('admin.login.show') }}';
            }).catch(() => {
                window.location.href = '{{ route('admin.login.show') }}';
            }));
        }
    }
    </script>
</body>
</html>

