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
        <nav class="container mx-auto px-4 py-4 flex items-center gap-3">
            <div class="flex items-center">
                <a href="{{ $analyticsUrl }}" class="flex items-center gap-2 hover:opacity-80 transition-opacity duration-200" aria-label="Dashboard">
                    <img src="{{ asset('logo.svg') }}" alt="TALMA Practice Pal" class="h-9 w-auto">
                </a>
            </div>
            <div class="flex-1 flex items-center justify-end gap-2">
                <button type="button" class="md:hidden p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-all duration-200" id="admin-nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="hidden md:flex items-center gap-2" id="admin-nav-links">
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
                </div>

                <!-- Account menu: org switcher, user info, logout -->
                <div class="relative" id="admin-account-menu">
                    <button type="button" class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200" id="admin-account-toggle" aria-label="Account and organization" aria-expanded="false" aria-haspopup="true">
                        <i class="fas fa-user-circle text-xl text-gray-500"></i>
                    </button>
                    <div class="hidden absolute top-full right-0 pt-1 z-50" id="admin-account-dropdown">
                        <div class="bg-white rounded-xl border border-gray-200/60 shadow-lg py-1 min-w-[220px]">
                            @if($accessibleOrgs->isNotEmpty())
                                <div class="px-4 py-2">
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Organization</p>
                                    @if($org)
                                        <p class="text-sm text-gray-600 mt-0.5 truncate">{{ $org->name }}</p>
                                    @endif
                                </div>
                                @foreach($accessibleOrgs as $o)
                                    <form method="POST" action="{{ route('admin.org.select.store') }}" class="block">
                                        @csrf
                                        <input type="hidden" name="organization" value="{{ $o->slug }}">
                                        <button type="submit" class="w-full text-left px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200 flex items-center justify-between {{ $org && $org->id === $o->id ? 'bg-blue-50/50 font-medium' : '' }}">
                                            <span class="truncate">{{ $o->name }}</span>
                                            @if($org && $org->id === $o->id)
                                                <i class="fas fa-check text-blue-600 text-sm ml-2 flex-shrink-0"></i>
                                            @endif
                                        </button>
                                    </form>
                                @endforeach
                                <div class="border-t border-gray-100 my-1"></div>
                            @endif
                            <div class="px-4 py-2.5 text-sm text-gray-600">
                                {{ auth('admin')->user()->name }} ({{ ucfirst(auth('admin')->user()->role) }})
                            </div>
                            <a href="#" onclick="logout(); return false;" class="block px-4 py-2.5 text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors duration-200">
                                <i class="fas fa-sign-out-alt mr-2 text-gray-400"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Mobile menu -->
        <div class="hidden md:hidden border-t border-gray-200/60 bg-white/95 backdrop-blur-sm" id="admin-mobile-nav">
            <div class="container mx-auto px-4 py-4 flex flex-col gap-1">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide px-3 pt-1">Courses &amp; Lessons</p>
                <a href="{{ $coursesUrl }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">
                    <i class="fas fa-book-open w-5 mr-2 text-gray-400"></i>Courses
                </a>
                @if($org)
                    <a href="{{ route('org.admin.classrooms.index', ['organization' => $org->slug]) }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">
                        <i class="fas fa-chalkboard-teacher w-5 mr-2 text-gray-400"></i>Classes
                    </a>
                @endif
                <a href="{{ route('admin.lessons.index') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">
                    <i class="fas fa-file-alt w-5 mr-2 text-gray-400"></i>All Lessons
                </a>
                <a href="{{ route('admin.lesson-tracker') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">
                    <i class="fas fa-tasks w-5 mr-2 text-gray-400"></i>Lesson Tracker
                </a>
                <a href="{{ route('admin.lessons.archived') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">
                    <i class="fas fa-archive w-5 mr-2 text-gray-400"></i>Archived
                </a>
                <a href="{{ route('admin.grammar-concepts.index') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">
                    <i class="fas fa-language w-5 mr-2 text-gray-400"></i>Grammar Sets
                </a>

                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide px-3 pt-3">Analytics</p>
                <a href="{{ $analyticsUrl }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">
                    <i class="fas fa-chart-line w-5 mr-2 text-gray-400"></i>Dashboard
                </a>
                <a href="{{ route('admin.session-length') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">
                    <i class="fas fa-clock w-5 mr-2 text-gray-400"></i>Session Length
                </a>
                <a href="{{ route('admin.openai-usage') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">
                    <i class="fas fa-dollar-sign w-5 mr-2 text-gray-400"></i>AI Usage &amp; Cost
                </a>

                @if(auth('admin')->user()?->role === 'admin')
                    <div class="border-t border-gray-200/60 my-2"></div>
                    <a href="{{ route('admin.organizations.index') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">
                        <i class="fas fa-building w-5 mr-2 text-gray-400"></i>Organizations
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">
                        <i class="fas fa-users w-5 mr-2 text-gray-400"></i>Users
                    </a>
                @endif

                <a href="{{ route('lessons.index') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200" title="View as student">
                    <i class="fas fa-graduation-cap w-5 mr-2 text-gray-400"></i>Student View
                </a>
            </div>
        </div>
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

    <script src="{{ asset('js/talma-audio.js') }}"></script>
    @stack('scripts')
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const navToggle = document.getElementById('admin-nav-toggle');
        const mobileNav = document.getElementById('admin-mobile-nav');

        if (navToggle && mobileNav) {
            navToggle.addEventListener('click', function() {
                const isOpen = !mobileNav.classList.contains('hidden');
                mobileNav.classList.toggle('hidden');
                navToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
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

        const accountToggle = document.getElementById('admin-account-toggle');
        const accountDropdown = document.getElementById('admin-account-dropdown');

        if (accountToggle && accountDropdown) {
            accountToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                const isOpen = !accountDropdown.classList.contains('hidden');
                accountDropdown.classList.toggle('hidden');
                accountToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            });

            document.addEventListener('click', function(e) {
                if (!document.getElementById('admin-account-menu').contains(e.target)) {
                    accountDropdown.classList.add('hidden');
                    accountToggle.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });

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

