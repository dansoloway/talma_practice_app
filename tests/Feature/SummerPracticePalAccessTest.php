<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Organization;
use App\Models\ParentStudent;
use App\Models\TermsAndCondition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummerPracticePalAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\OrganizationSeeder::class);
        $this->seed(\Database\Seeders\RootOrganizationSeeder::class);
        $this->seed(\Database\Seeders\TermsAndConditionsSeeder::class);
    }

    protected function summerOrg(array $overrides = []): Organization
    {
        return Organization::create(array_merge([
            'name' => 'Summer Practice Pal',
            'slug' => Organization::SUMMER_PRACTICE_PAL_SLUG,
            'access_mode' => 'restricted',
            'allow_self_registration' => true,
            'registration_type' => Organization::REGISTRATION_TYPE_PARENT_SIGNUP,
            'is_active' => true,
        ], $overrides));
    }

    public function test_guest_is_redirected_to_summer_login(): void
    {
        $org = $this->summerOrg();

        $response = $this->get(route('org.student.index', $org));

        $response->assertRedirect(route('org.student.login', $org));
    }

    public function test_parent_register_defaults_to_hebrew(): void
    {
        $org = $this->summerOrg();

        $this->get(route('org.student.register', $org))
            ->assertOk()
            ->assertSee('רישום הורה / אפוטרופוס', false)
            ->assertSee('togglePasswordField', false);
    }

    public function test_parent_register_can_switch_to_english(): void
    {
        $org = $this->summerOrg();

        $this->get(route('org.student.register', ['organization' => $org, 'lang' => 'en']))
            ->assertOk()
            ->assertSee('Parent or guardian registration', false);
    }

    public function test_parent_login_defaults_to_hebrew(): void
    {
        $org = $this->summerOrg();

        $this->get(route('org.student.login', $org))
            ->assertOk()
            ->assertSee('התחברו כדי לגשת לקורסים שלכם', false)
            ->assertSee('עברית', false);
    }

    public function test_parent_login_can_switch_to_arabic(): void
    {
        $org = $this->summerOrg();

        $this->get(route('org.student.login', ['organization' => $org, 'lang' => 'ar']))
            ->assertOk()
            ->assertSee('سجّل الدخول للوصول إلى دوراتك', false)
            ->assertSee('lang="ar"', false);
    }

    public function test_parent_can_register_and_access_org_courses(): void
    {
        $org = $this->summerOrg();

        $course = Course::create([
            'title' => 'Summer Practice Pal — Pre-A1',
            'slug' => 'summer-practice-pal-pre-a1',
            'is_active' => true,
        ]);

        $org->courses()->attach($course->id, ['is_org_wide' => true]);

        $response = $this->post(route('org.student.register.submit', $org), [
            'name' => 'Jane Parent',
            'hebrew_name' => 'ג׳יין הורה',
            'id_number' => '123456789',
            'email' => 'parent@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone_prefix' => '050',
            'phone_rest' => '1234567',
            'terms_accepted' => '1',
            'students' => [
                [
                    'first_name' => 'דן',
                    'last_name' => 'למד',
                    'first_name_english' => 'Dan',
                    'last_name_english' => 'Learner',
                    'birth_year' => '2015',
                    'grade' => '4',
                    'gender' => 'male',
                    'native_language' => 'hebrew',
                    'login_type' => 'shared',
                ],
            ],
        ]);

        $response->assertRedirect(route('org.student.index', $org));
        $this->assertAuthenticated('admin');

        $user = User::where('email', 'parent@example.com')->first();
        $this->assertTrue($user->isParent());
        $this->assertTrue($user->isMemberOfOrg($org->id));
        $this->assertNotNull($user->terms_accepted_at);

        $child = ParentStudent::where('parent_id', $user->id)->first();
        $this->assertNotNull($child);
        $this->assertSame('hebrew', $child->native_language);

        $this->get(route('org.student.index', $org))
            ->assertOk()
            ->assertSee('Summer Practice Pal — Pre-A1');
    }

    public function test_student_self_signup_org_still_works_with_terms(): void
    {
        TermsAndCondition::getStudentSignupTerms();

        $org = Organization::create([
            'name' => 'Simple Org',
            'slug' => 'simple-org',
            'access_mode' => 'restricted',
            'allow_self_registration' => true,
            'registration_type' => Organization::REGISTRATION_TYPE_STUDENT,
            'is_active' => true,
        ]);

        $response = $this->post(route('org.student.register.submit', $org), [
            'name' => 'Test Learner',
            'email' => 'learner@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms_accepted' => '1',
        ]);

        $response->assertRedirect(route('org.student.index', $org));
        $this->assertAuthenticated('admin');

        $user = User::where('email', 'learner@example.com')->first();
        $this->assertTrue($user->isStudent());
        $this->assertNotNull($user->terms_accepted_at);
    }

    public function test_restricted_only_course_requires_auth_on_legacy_route(): void
    {
        $org = $this->summerOrg();

        $course = Course::create([
            'title' => 'Summer Practice Pal — Pre-A1',
            'slug' => 'summer-practice-pal-pre-a1',
            'is_active' => true,
        ]);

        $org->courses()->attach($course->id, ['is_org_wide' => true]);

        $lesson = \App\Models\Lesson::create([
            'course_id' => $course->id,
            'title' => 'Test Lesson',
            'slug' => 'summer-test-lesson',
            'is_active' => true,
        ]);

        $response = $this->get(route('lessons.show', $lesson->slug));

        $response->assertRedirect(route('org.student.login', $org));
    }

    public function test_parent_with_two_children_is_prompted_to_select_child(): void
    {
        $org = $this->summerOrg();

        $this->post(route('org.student.register.submit', $org), [
            'name' => 'Jane Parent',
            'hebrew_name' => 'ג׳יין',
            'id_number' => '987654321',
            'email' => 'parent2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone_prefix' => '052',
            'phone_rest' => '7654321',
            'terms_accepted' => '1',
            'students' => [
                [
                    'first_name' => 'א',
                    'last_name' => 'א',
                    'first_name_english' => 'Amy',
                    'last_name_english' => 'One',
                    'birth_year' => '2014',
                    'grade' => '5',
                    'gender' => 'female',
                    'native_language' => 'arabic',
                    'login_type' => 'shared',
                ],
                [
                    'first_name' => 'ב',
                    'last_name' => 'ב',
                    'first_name_english' => 'Ben',
                    'last_name_english' => 'Two',
                    'birth_year' => '2016',
                    'grade' => '3',
                    'gender' => 'male',
                    'native_language' => 'english',
                    'login_type' => 'shared',
                ],
            ],
        ])->assertRedirect(route('org.student.select-child', $org));
    }
}
