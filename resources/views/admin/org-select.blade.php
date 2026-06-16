@extends('layouts.admin')

@section('title', 'Select Organization')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white/90 backdrop-blur-sm rounded-2xl border border-gray-200/60 shadow-xl p-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Select Organization</h1>
        <p class="text-gray-600 mb-6">Choose an organization to manage.</p>

        @if(auth('admin')->user()?->role === 'admin')
            <a href="{{ route('admin.organizations.index') }}" class="inline-block mb-4 text-blue-600 hover:text-blue-700 font-medium">
                <i class="fas fa-plus mr-1"></i>Create or manage organizations
            </a>
        @endif

        @if($organizations->isEmpty())
            <p class="text-gray-600">You do not have access to any organizations.</p>
            @if(auth('admin')->user()?->role === 'admin')
                <a href="{{ route('admin.organizations.create') }}" class="inline-block mt-4 btn btn-primary">Create your first organization</a>
            @endif
            <a href="{{ route('admin.logout') }}" class="inline-block mt-4 ml-2 text-blue-600 hover:text-blue-700 font-medium" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
            <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="hidden">@csrf</form>
        @else
            <div class="space-y-3">
                @foreach($organizations as $org)
                    <form method="POST" action="{{ route('admin.org.select.store') }}" class="block">
                        @csrf
                        <input type="hidden" name="organization" value="{{ $org->slug }}">
                        <button type="submit" class="w-full text-left px-6 py-4 border border-gray-200 rounded-xl hover:border-blue-400 hover:bg-blue-50/50 transition-all duration-200 flex items-center justify-between">
                            <span class="font-semibold text-gray-800">{{ $org->name }}</span>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </button>
                    </form>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
