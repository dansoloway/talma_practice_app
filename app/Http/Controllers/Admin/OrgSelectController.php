<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrgSelectController extends Controller
{
    /**
     * Display list of organizations the user can access.
     */
    public function index(Request $request)
    {
        $user = Auth::guard('admin')->user();

        if ($user->role === 'admin') {
            $organizations = Organization::where('is_active', true)->orderBy('name')->get();
        } else {
            $organizations = $user->organizations()
                ->where('organizations.is_active', true)
                ->wherePivotIn('role', ['org_admin', 'teacher'])
                ->orderBy('organizations.name')
                ->get();
        }

        return view('admin.org-select', compact('organizations'));
    }

    /**
     * Store selected org in session and redirect to org admin.
     */
    public function store(Request $request)
    {
        $slug = $request->validate(['organization' => 'required|string|exists:organizations,slug'])['organization'];

        $request->session()->put('admin_last_org_slug', $slug);

        return redirect()->route('org.admin.analytics', ['organization' => $slug]);
    }
}
