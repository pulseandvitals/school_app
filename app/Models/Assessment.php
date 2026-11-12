<?php

namespace App\Models;

use App\Enums\AssessmentType;
use Database\Factories\AssessmentFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'type', 'custom_type', 'assessed_on', 'total_score', 'description'])]
#[Appends(['type_label'])]
class Assessment extends Model
{
    /** @use HasFactory<AssessmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => AssessmentType::class,
            'assessed_on' => 'date:Y-m-d',
            'total_score' => 'decimal:2',
        ];
    }

    public function habitat(): BelongsTo
    {
        return $this->belongsTo(Habitat::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AssessmentScore::class);
    }

    /**
     * Human label, using the teacher's own wording for custom types.
     */
    protected function typeLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->type === AssessmentType::Custom && $this->custom_type
            ? $this->custom_type
            : $this->type->label());
    }
}
