<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentIdentity extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'email',
        'phone_number',
        'login_type',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(ParentStudent::class, 'student_id');
    }
}
