<?php

namespace Tests\Unit;

use App\Models\ParentStudent;
use App\Models\User;
use App\Support\PracticeLearnerScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeLearnerScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_uses_browser_session_cookie(): void
    {
        $this->assertSame(
            'browser-session-123',
            PracticeLearnerScope::forUser(null, 'browser-session-123')
        );
    }

    public function test_student_uses_user_scope(): void
    {
        $user = User::create([
            'name' => 'Learner',
            'email' => 'learner-scope@example.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $this->assertSame(
            'user:'.$user->id,
            PracticeLearnerScope::forUser($user, 'browser-session-123')
        );
    }

    public function test_parent_uses_selected_child_scope(): void
    {
        $parent = User::create([
            'name' => 'Parent',
            'email' => 'parent-scope@example.com',
            'password' => bcrypt('password'),
            'role' => User::ROLE_PARENT,
            'is_active' => true,
        ]);

        $child = ParentStudent::create([
            'parent_id' => $parent->id,
            'first_name' => 'Kid',
            'last_name' => 'One',
            'birth_date' => '2015-01-01',
            'gender' => 'female',
            'native_language' => 'hebrew',
        ]);

        session(['selected_student_id' => $child->id]);

        $this->assertSame(
            'child:'.$child->id,
            PracticeLearnerScope::forUser($parent, 'browser-session-123')
        );
    }

    public function test_admin_preview_uses_browser_session_cookie(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-scope@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->assertSame(
            'browser-session-123',
            PracticeLearnerScope::forUser($admin, 'browser-session-123')
        );
    }
}
