<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'student_number'])]
#[Appends(['photo_url'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The owning teacher.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Reserved for a future student portal login.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_user_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(HabitatMember::class);
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->photo_path
            ? Storage::disk('public')->url($this->photo_path)
            : null);
    }
}
