@if(auth('admin')->user()?->canAccessAdmin())
    <div class="flex flex-wrap items-center gap-2 {{ $class ?? '' }}">
        <a href="{{ route('admin.lessons.manage', $lesson) }}"
           class="inline-flex items-center gap-2 px-3 py-2 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all duration-200 shadow-sm">
            <i class="fas fa-pen-to-square" aria-hidden="true"></i>
            Edit Lesson
        </a>
        @if(!empty($activityEditUrl))
            <a href="{{ $activityEditUrl }}"
               class="inline-flex items-center gap-2 px-3 py-2 text-sm font-semibold text-blue-700 bg-blue-100 rounded-xl hover:bg-blue-200 transition-all duration-200">
                <i class="fas fa-cog" aria-hidden="true"></i>
                {{ $activityEditLabel ?? 'Edit Activity' }}
            </a>
        @endif
    </div>
@endif
