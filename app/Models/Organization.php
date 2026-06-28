<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    /**
     * System invariant: only one Root organization may exist.
     * Root cannot be deleted or converted to tenant. Enforced in application.
     */
    protected static function booted(): void
    {
        static::creating(function (Organization $org) {
            if ($org->is_root && static::where('is_root', true)->exists()) {
                throw new \LogicException('Only one Root organization may exist.');
            }
        });
        static::updating(function (Organization $org) {
            if ($org->is_root) {
                if ($org->getOriginal('is_root') !== true && static::where('is_root', true)->where('id', '!=', $org->id)->exists()) {
                    throw new \LogicException('Only one Root organization may exist.');
                }
            } elseif ($org->getOriginal('is_root') === true) {
                throw new \LogicException('Root organization cannot be converted to a tenant organization.');
            }
        });
        static::deleting(function (Organization $org) {
            if ($org->is_root) {
                throw new \LogicException('Root organization cannot be deleted.');
            }
        });
    }

    public const SUMMER_PRACTICE_PAL_SLUG = 'summer-practice-pal';

    public const REGISTRATION_TYPE_STUDENT = 'student';

    public const REGISTRATION_TYPE_PARENT_SIGNUP = 'parent_signup';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'access_mode',
        'allow_self_registration',
        'registration_type',
        'retain_voice_recordings',
        'is_root',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_root' => 'boolean',
        'allow_self_registration' => 'boolean',
        'retain_voice_recordings' => 'boolean',
    ];

    /**
     * Get the route key for the model (slug for URL binding).
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Display name for student-facing views.
     * The default org slug displays as "TALMA Community Resources".
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->slug === 'default' ? 'TALMA Community Resources' : $this->name;
    }

    public function usesParentSignup(): bool
    {
        return $this->registration_type === self::REGISTRATION_TYPE_PARENT_SIGNUP;
    }

    public function usesStudentSignup(): bool
    {
        return ! $this->usesParentSignup();
    }

    /**
     * Users belonging to this organization (with pivot role).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Courses accessible to this organization (with pivot is_org_wide).
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'organization_course')
            ->withPivot('is_org_wide')
            ->withTimestamps();
    }

    /**
     * Classes in this organization.
     */
    public function classes(): HasMany
    {
        return $this->hasMany(Classroom::class, 'organization_id');
    }

    /**
     * The Root organization (system-level). Null if not seeded.
     */
    public static function root(): ?Organization
    {
        return static::where('is_root', true)->first();
    }
}
