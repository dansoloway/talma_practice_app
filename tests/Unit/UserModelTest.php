<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_admin_is_teacher_can_access_admin_behave_correctly_for_admin_and_teacher_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isTeacher());
        $this->assertTrue($admin->canAccessAdmin());

        $teacher = User::factory()->teacher()->create();
        $this->assertFalse($teacher->isAdmin());
        $this->assertTrue($teacher->isTeacher());
        $this->assertTrue($teacher->canAccessAdmin());
    }

    public function test_other_role_values_return_false_for_admin_access(): void
    {
        $user = new User(['role' => 'student']);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isTeacher());
        $this->assertFalse($user->canAccessAdmin());

        $user->role = 'moderator';
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isTeacher());
        $this->assertFalse($user->canAccessAdmin());
    }
}
