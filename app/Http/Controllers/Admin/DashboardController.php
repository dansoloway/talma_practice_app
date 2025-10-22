<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Prompt;
use App\Models\Option;
use App\Models\Response;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $stats = [
            'lessons_count' => Lesson::count(),
            'prompts_count' => Prompt::count(),
            'options_count' => Option::count(),
            'responses_count' => Response::count(),
        ];

        $recentLessons = Lesson::latest()->take(5)->get();
        $recentResponses = Response::with(['lesson', 'prompt', 'option'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentLessons', 'recentResponses'));
    }
}

