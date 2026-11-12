<?php

namespace App\Models;

use App\Enums\GameAssetType;
use Database\Factories\GameAssetFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'name', 'image_path', 'asset_number', 'stage_images', 'is_active'])]
#[Appends(['image_url', 'stage_image_urls'])]
class GameAsset extends Model
{
    /** @use HasFactory<GameAssetFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => GameAssetType::class,
            'stage_images' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(HabitatMember::class);
    }

    #[Scope]
    protected function ofType(Builder $query, GameAssetType $type): Builder
    {
        return $query->where('type', $type);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Public URL of the base image.
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn (): string => asset($this->image_path));
    }

    /**
     * Optional per-stage image URLs keyed by stage number.
     */
    protected function stageImageUrls(): Attribute
    {
        return Attribute::get(fn (): array => array_map(
            fn (string $path): string => asset($path),
            $this->stage_images ?? [],
        ));
    }
}
