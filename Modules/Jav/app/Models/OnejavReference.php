<?php

namespace Modules\Jav\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Jav\Database\Factories\OnejavReferenceFactory;
use Modules\Jav\Models\Interfaces\IJavMovie;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string $cover
 * @property string $dvd_id
 * @property string $title
 * @property float $size
 * @property array $gallery
 * @property array $genres
 * @property array $performers
 */
class OnejavReference extends Model implements IJavMovie
{
    use HasFactory;

    protected $table = 'onejav';

    protected $connection = 'mongodb';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'url',
        'cover',
        'dvd_id',
        'size',
        'release_date',
        'genres',
        'description',
        'performers',
        'torrent',
        'gallery',
    ];

    protected $casts = [
        'url' => 'string',
        'cover' => 'string',
        'dvd_id' => 'string',
        'size' => 'float',
        'release_date' => 'date',
        'genres' => 'array',
        'description' => 'string',
        'performers' => 'array',
        'torrent' => 'string',
        'gallery' => 'array',
    ];

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDvdId(): ?string
    {
        return $this->dvd_id;
    }

    public function getSize(): ?float
    {
        return $this->size;
    }

    public function getGallery(): ?array
    {
        return $this->gallery;
    }

    protected static function newFactory(): OnejavReferenceFactory
    {
        return OnejavReferenceFactory::new();
    }

    public function getGenres(): ?array
    {
        return $this->genres;
    }

    public function getPerformers(): ?array
    {
        return $this->performers;
    }
}
