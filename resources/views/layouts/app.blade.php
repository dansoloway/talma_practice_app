<!DOCTYPE html>
@php
    use App\Support\SignupLocale;
    $portalLocale = 'en';
    $portalDir = 'ltr';
    if (isset($currentOrganization) && $currentOrganization->usesParentSignup()) {
        $portalLocale = app()->getLocale();
        $portalDir = SignupLocale::isRtl($portalLocale) ? 'rtl' : 'ltr';
    }
@endphp
<html lang="@yield('html_lang', $portalLocale)" dir="@yield('html_dir', $portalDir)">
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
    @php
        $authUser = auth('admin')->user();
        $isOrgStudentPortal = isset($currentOrganization) && $authUser && ($authUser->isStudent() || $authUser->isParent());
        $selectedStudent = ($authUser && session('selected_student_id'))
            ? \App\Models\ParentStudent::find(session('selected_student_id'))
            : null;
        $studentHomeUrl = isset($currentOrganization)
            ? route('org.student.index', $currentOrganization)
            : route('lessons.index');
    @endphp
    <header class="bg-white/90 backdrop-blur-sm border-b border-gray-200/60 shadow-sm sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <a href="{{ $isOrgStudentPortal ? $studentHomeUrl : route('lessons.index') }}" class="flex items-center gap-3 hover:opacity-80 transition-opacity duration-200" aria-label="TALMA Practice Pal home">
                    <img src="{{ asset('logo.svg') }}" alt="TALMA Practice Pal" class="h-9 w-auto">
                </a>
                @if($isOrgStudentPortal)
                    <span class="hidden sm:inline ml-3 text-sm font-medium text-gray-600 border-l border-gray-200 pl-3">{{ $currentOrganization->display_name }}</span>
                @endif
            </div>

            @if($isOrgStudentPortal)
                <div class="flex items-center gap-2">
                    @if($currentOrganization->usesParentSignup())
                        <x-signup-locale-switcher compact />
                    @endif
                    <div class="relative" id="student-account-menu">
                        <button type="button" class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-200" id="student-account-toggle" aria-label="{{ __('student-portal.nav.account') }}" aria-expanded="false" aria-haspopup="true">
                            <i class="fas fa-user-circle text-2xl text-gray-500"></i>
                        </button>
                        <div class="hidden absolute top-full right-0 pt-1 z-50" id="student-account-dropdown">
                            <div class="bg-white rounded-xl border border-gray-200/60 shadow-lg py-1 min-w-[220px]">
                                <div class="px-4 py-2.5 text-sm text-gray-800 font-medium">
                                    {{ $authUser->name }}
                                </div>
                                @if($authUser->isParent() && $selectedStudent && $currentOrganization->usesParentSignup())
                                    <div class="px-4 pb-2 text-xs text-gray-500">
                                        {{ __('student-portal.nav.practicing_as', ['name' => $selectedStudent->display_name]) }}
                                    </div>
                                    <a href="{{ route('org.student.select-child', $currentOrganization) }}" class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">
                                        <i class="fas fa-exchange-alt mr-2 text-gray-400"></i>{{ __('student-portal.nav.switch_child') }}
                                    </a>
                                @endif
                                <div class="border-t border-gray-100 my-1"></div>
                                <form method="POST" action="{{ route('org.student.logout', $currentOrganization) }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors duration-200 rounded-b-xl">
                                        <i class="fas fa-sign-out-alt mr-2 text-gray-400"></i>{{ __('student-portal.nav.logout') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @else
            <button class="md:hidden p-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-all duration-200" id="nav-toggle" aria-label="Toggle navigation">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="hidden md:flex items-center gap-6" id="nav-links">
                <a href="{{ route('lessons.index') }}" class="text-gray-700 hover:text-blue-600 font-medium transition-colors duration-200 px-3 py-2 rounded-lg hover:bg-blue-50">Lessons</a>
                
                <!-- Analytics Dropdown -->
                <div class="relative group">
                    <a href="#" class="flex items-center gap-1.5 px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">
                        Analytics <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-hover:rotate-180"></i>
                    </a>
                    <div class="absolute top-full left-0 mt-1 w-48 bg-white rounded-xl border border-gray-200/60 shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <a href="{{ auth('admin')->check() ? route('admin.analytics') : route('admin.dashboard') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-t-xl transition-colors duration-200">Dashboard</a>
                        <a href="{{ auth('admin')->check() ? route('admin.session-length') : route('admin.dashboard') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">Session Length</a>
                        <a href="{{ auth('admin')->check() ? route('admin.openai-usage') : route('admin.dashboard') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-b-xl transition-colors duration-200">OpenAI Usage & Cost</a>
                    </div>
                </div>
                
                <!-- Courses -->
                <a href="{{ auth('admin')->check() ? route('admin.courses.index') : route('admin.dashboard') }}" class="px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">Courses</a>
                
                <!-- Lessons Dropdown -->
                <div class="relative group">
                    <a href="#" class="flex items-center gap-1.5 px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">
                        Lessons <i class="fas fa-chevron-down text-xs transition-transform duration-200 group-hover:rotate-180"></i>
                    </a>
                    <div class="absolute top-full left-0 mt-1 w-48 bg-white rounded-xl border border-gray-200/60 shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <a href="{{ auth('admin')->check() ? route('admin.lessons.index') : route('admin.dashboard') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-t-xl transition-colors duration-200">All Lessons</a>
                        <a href="{{ auth('admin')->check() ? route('admin.lesson-tracker') : route('admin.dashboard') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">Lesson Tracker</a>
                        <a href="{{ auth('admin')->check() ? route('admin.lessons.archived') : route('admin.dashboard') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-200">Archived</a>
                        <a href="{{ auth('admin')->check() ? route('admin.grammar-concepts.index') : route('admin.dashboard') }}" class="block px-4 py-2.5 text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-b-xl transition-colors duration-200">Grammar Sets</a>
                    </div>
                </div>
                
                @if(auth('admin')->check() && auth('admin')->user()->role === 'admin')
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">Users</a>
                @endif
                
                <a href="{{ auth('admin')->check() ? route('admin.openai-usage') : route('admin.dashboard') }}" title="View AI Cost Dashboard" class="px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">
                    <i class="fas fa-dollar-sign"></i> AI Costs
                </a>
                
                @if(auth('admin')->check())
                    @php
                        $legacySelectedStudent = session('selected_student_id') ? \App\Models\ParentStudent::find(session('selected_student_id')) : null;
                    @endphp
                    @if($legacySelectedStudent && isset($currentOrganization) && $currentOrganization->usesParentSignup())
                        <a href="{{ route('org.student.select-child', $currentOrganization) }}" class="px-3 py-2 text-sm text-blue-700 bg-blue-50 font-medium rounded-lg hover:bg-blue-100 transition-all duration-200">
                            Practicing as: {{ $legacySelectedStudent->display_name }}
                        </a>
                    @endif
                    <span class="px-3 py-2 text-gray-600 text-sm border-l border-gray-200/60 ml-2 pl-4">{{ auth('admin')->user()->name }} ({{ ucfirst(auth('admin')->user()->role) }})</span>
                    @if(isset($currentOrganization) && auth('admin')->user()->canAccessStudentPortal() && ! auth('admin')->user()->isStudent() && ! auth('admin')->user()->isParent())
                        <form method="POST" action="{{ route('org.student.logout', $currentOrganization) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-2 text-gray-700 hover:text-red-600 font-medium rounded-lg hover:bg-red-50 transition-all duration-200">Logout</button>
                        </form>
                    @elseif(auth('admin')->user()->canAccessAdmin())
                        <a href="#" onclick="logout()" class="px-3 py-2 text-gray-700 hover:text-red-600 font-medium rounded-lg hover:bg-red-50 transition-all duration-200">Logout</a>
                    @endif
                @else
                    <a href="{{ route('admin.dashboard') }}" class="px-3 py-2 text-gray-700 hover:text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-all duration-200">Login</a>
                @endif
            </div>
            @endif
        </nav>
        <!-- Mobile menu (legacy / admin nav only) -->
        @unless($isOrgStudentPortal)
        <div class="hidden md:hidden border-t border-gray-200/60 bg-white/95 backdrop-blur-sm" id="mobile-nav">
            <div class="container mx-auto px-4 py-4 flex flex-col gap-2">
                <a href="{{ route('lessons.index') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">Lessons</a>
                
                <a href="{{ auth('admin')->check() ? route('admin.analytics') : route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">Analytics Dashboard</a>
                <a href="{{ auth('admin')->check() ? route('admin.session-length') : route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">Session Length</a>
                <a href="{{ auth('admin')->check() ? route('admin.openai-usage') : route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">OpenAI Usage</a>
                <a href="{{ auth('admin')->check() ? route('admin.courses.index') : route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">Courses</a>
                <a href="{{ auth('admin')->check() ? route('admin.lessons.index') : route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">All Lessons</a>
                <a href="{{ auth('admin')->check() ? route('admin.lesson-tracker') : route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">Lesson Tracker</a>
                <a href="{{ auth('admin')->check() ? route('admin.lessons.archived') : route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">Archived</a>
                <a href="{{ auth('admin')->check() ? route('admin.grammar-concepts.index') : route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">Grammar Sets</a>
                @if(auth('admin')->check() && auth('admin')->user()->role === 'admin')
                    <a href="{{ route('admin.users.index') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">Users</a>
                @endif
                
                @if(auth('admin')->check())
                    <div class="border-t border-gray-200/60 my-2 pt-2">
                        <span class="text-gray-600 text-sm px-3">{{ auth('admin')->user()->name }} ({{ ucfirst(auth('admin')->user()->role) }})</span>
                    </div>
                    @if(isset($currentOrganization) && auth('admin')->user()->canAccessStudentPortal() && ! auth('admin')->user()->isStudent() && ! auth('admin')->user()->isParent())
                        <form method="POST" action="{{ route('org.student.logout', $currentOrganization) }}" class="px-3">
                            @csrf
                            <button type="submit" class="text-gray-700 hover:text-red-600 font-medium py-2">Logout</button>
                        </form>
                    @elseif(auth('admin')->user()->canAccessAdmin())
                        <a href="#" onclick="logout()" class="text-gray-700 hover:text-red-600 font-medium py-2 px-3 rounded-lg hover:bg-red-50 transition-all duration-200">Logout</a>
                    @endif
                @else
                    <a href="{{ route('admin.dashboard') }}" class="text-gray-700 hover:text-blue-600 font-medium py-2 px-3 rounded-lg hover:bg-blue-50 transition-all duration-200">Login</a>
                @endif
            </div>
        </div>
        @endunless
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

        const accountToggle = document.getElementById('student-account-toggle');
        const accountDropdown = document.getElementById('student-account-dropdown');
        const accountMenu = document.getElementById('student-account-menu');

        if (accountToggle && accountDropdown && accountMenu) {
            accountToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                const isOpen = !accountDropdown.classList.contains('hidden');
                accountDropdown.classList.toggle('hidden');
                accountToggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
            });

            document.addEventListener('click', function(e) {
                if (!accountMenu.contains(e.target)) {
                    accountDropdown.classList.add('hidden');
                    accountToggle.setAttribute('aria-expanded', 'false');
                }
            });
        }
    });
    
    // Admin logout function
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

    <script src="{{ asset('js/talma-audio.js') }}"></script>
    <script src="{{ asset('js/talma-speech.js') }}"></script>
    @stack('scripts')
</body>
</html>

