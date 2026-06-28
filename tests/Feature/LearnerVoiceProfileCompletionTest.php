<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Organization;
use App\Models\ParentStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerVoiceProfileCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::create([
            'name' => 'Voice Profile Org',
            'slug' => 'voice-profile-org',
            'access_mode' => 'restricted',
            'allow_self_registration' => true,
            'registration_type' => Organization::REGISTRATION_TYPE_STUDENT,
            'retain_voice_recordings' => true,
            'is_active' => true,
        ]);
    }

    public function test_incomplete_student_is_redirected_to_complete_profile(): void
    {
        $user = User::create([
            'name' => 'Incomplete Student',
            'email' => 'incomplete@example.com',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $this->organization->users()->attach($user->id, ['role' => 'student']);

        $response = $this->actingAs($user, 'admin')->get(route('org.student.index', $this->organization));

        $response->assertRedirect(route('org.student.complete-voice-profile', $this->organization));
    }

    public function test_student_can_complete_profile_and_access_courses(): void
    {
        $user = User::create([
            'name' => 'New Student',
            'email' => 'newstudent@example.com',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $this->organization->users()->attach($user->id, ['role' => 'student']);

        $course = Course::create([
            'title' => 'Test Course',
            'slug' => 'test-course',
            'is_active' => true,
        ]);

        $this->organization->courses()->attach($course->id, ['is_org_wide' => true]);

        $this->actingAs($user, 'admin')->get(route('org.student.index', $this->organization))
            ->assertRedirect(route('org.student.complete-voice-profile', $this->organization));

        $response = $this->actingAs($user, 'admin')->post(route('org.student.complete-voice-profile.submit', $this->organization), [
            'age' => 10,
            'gender' => 'female',
            'native_language' => 'hebrew',
            'voice_recording_consent' => '1',
        ]);

        $response->assertRedirect(route('org.student.index', $this->organization));

        $this->actingAs($user, 'admin')->get(route('org.student.index', $this->organization))
            ->assertOk();
    }

    public function test_parent_with_consent_can_access_even_if_child_profile_incomplete(): void
    {
        $this->organization->update([
            'registration_type' => Organization::REGISTRATION_TYPE_PARENT_SIGNUP,
        ]);

        $parent = User::create([
            'name' => 'Parent User',
            'email' => 'parent@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_PARENT,
            'is_active' => true,
            'terms_accepted_at' => now(),
            'voice_recording_consented_at' => now(),
        ]);

        $this->organization->users()->attach($parent->id, ['role' => 'parent']);

        $child = ParentStudent::create([
            'parent_id' => $parent->id,
            'first_name' => 'Test',
            'last_name' => 'Child',
            'first_name_english' => 'Test',
            'last_name_english' => 'Child',
            'birth_date' => null,
            'gender' => null,
            'native_language' => null,
        ]);

        session(['selected_student_id' => $child->id]);

        $this->actingAs($parent, 'admin')->get(route('org.student.index', $this->organization))
            ->assertOk();
    }

    public function test_parent_without_voice_consent_is_redirected_to_complete_profile(): void
    {
        $this->organization->update([
            'registration_type' => Organization::REGISTRATION_TYPE_PARENT_SIGNUP,
        ]);

        $parent = User::create([
            'name' => 'No Consent Parent',
            'email' => 'noconsent@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_PARENT,
            'is_active' => true,
        ]);

        $this->organization->users()->attach($parent->id, ['role' => 'parent']);

        $this->actingAs($parent, 'admin')->get(route('org.student.index', $this->organization))
            ->assertRedirect(route('org.student.complete-voice-profile', $this->organization));
    }
}
