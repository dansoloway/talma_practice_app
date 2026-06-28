<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public const GENDER_MALE = 'male';

    public const GENDER_FEMALE = 'female';

    public const NATIVE_LANGUAGES = ParentStudent::NATIVE_LANGUAGES;

    public const ROLE_PARENT = 'parent';

    protected $fillable = [
        'name',
        'hebrew_name',
        'id_number',
        'email',
        'phone_number',
        'city_id',
        'password',
        'role',
        'is_active',
        'age',
        'gender',
        'native_language',
        'voice_recording_consented_at',
        'terms_accepted_at',
        'terms_version',
        'remember_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'voice_recording_consented_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
        ];
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a teacher.
     */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    /**
     * Check if user is a student (learner portal).
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isParent(): bool
    {
        return $this->role === self::ROLE_PARENT;
    }

    /**
     * Check if user has admin or teacher access.
     */
    public function canAccessAdmin(): bool
    {
        return $this->isAdmin() || $this->isTeacher();
    }

    /**
     * Check if user can use the student-facing org portal.
     */
    public function canAccessStudentPortal(): bool
    {
        return $this->is_active && ($this->isStudent() || $this->isParent() || $this->isAdmin());
    }

    /**
     * Scope to get only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get users by role.
     */
    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Organizations the user belongs to (with pivot role).
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Classes the user is enrolled in as a student.
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'class_user', 'user_id', 'class_id')
            ->withTimestamps();
    }

    /**
     * Classes the user teaches.
     */
    public function teachingClasses(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'class_teacher', 'user_id', 'class_id')
            ->withTimestamps();
    }

    /**
     * Get the user's role in an organization.
     */
    public function orgRole(int $orgId): ?string
    {
        $pivot = $this->organizations()->where('organizations.id', $orgId)->first()?->pivot;

        return $pivot?->role;
    }

    /**
     * Check if the user is a member of an organization (any role).
     */
    public function isMemberOfOrg(int $orgId): bool
    {
        return $this->organizations()->where('organizations.id', $orgId)->exists();
    }

    /**
     * Whether this user opted in to anonymized voice recording retention.
     */
    public function hasVoiceRecordingConsent(): bool
    {
        return $this->voice_recording_consented_at !== null
            && $this->age !== null
            && $this->gender !== null
            && $this->native_language !== null;
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function parentStudents(): HasMany
    {
        return $this->hasMany(ParentStudent::class, 'parent_id');
    }

    public function linkedParentStudent(): HasOne
    {
        return $this->hasOne(ParentStudent::class, 'user_id');
    }

    public function sharedLoginChildren(): HasMany
    {
        return $this->parentStudents()->where(function ($query) {
            $query->whereDoesntHave('identity')
                ->orWhereHas('identity', fn ($q) => $q->where('login_type', 'shared'));
        });
    }
}
