<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'access_mode',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the route key for the model (slug for URL binding).
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
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
}
