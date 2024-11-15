<?php

namespace Modules\Jav\Dto;

use Carbon\Carbon;
use Modules\Core\Dto\AbstractBaseDto;
use Modules\Core\Dto\Traits\TDefaultDto;
use Modules\Jav\Helpers\OnejavHelper;

/**
 * @property string $url
 * @property string $cover
 * @property string $dvd_id
 * @property float $size
 * @property Carbon $date
 * @property array $genres
 * @property string $description
 * @property array $performers
 * @property array $gallery
 * @property string $torrent
 */
class ItemDto extends AbstractBaseDto
{
    use TDefaultDto;

    public function transform(mixed $response): ?static
    {
        $this->data = new \stdClass();

        if ($response->filter('h5.title a')->count()) {
            $this->url = $response->filter('h5.title a')->attr('href');
        }

        if ($response->filter('.columns img.image')->count()) {
            $this->cover = $response->filter('.columns img.image')->attr('src');
        }

        if ($response->filter('h5 a')->count()) {
            $this->dvd_id = $response->filter('h5 a')->text(null, false);
            $this->dvd_id = OnejavHelper::parseDvdId($this->dvd_id);
        }

        if ($response->filter('h5 span')->count()) {
            $this->size = $response->filter('h5 span')->text(null, false);
            $this->size = OnejavHelper::convertSize($this->size);
        }

        // Always use href because it'll never change but text will be
        $this->date = OnejavHelper::convertToDate(trim($response->filter('.subtitle.is-6 a')->attr('href')));
        $this->genres = collect($response->filter('.tags .tag')->each(
            function ($genres) {
                return trim($genres->text(null, false));
            }
        ))->reject(function ($value) {
            return empty($value);
        })->unique()->toArray();

        // Description
        $description = $response->filter('.level.has-text-grey-dark');
        $this->description = $description->count() ? trim($description->text(null, false)) : null;
        $this->description = preg_replace("/\r|\n/", '', $this->description);

        $this->performers = collect($response->filter('.panel .panel-block')->each(
            function ($performers) {
                return trim($performers->text(null, false));
            }
        ))->reject(function ($value) {
            return empty($value);
        })->unique()->toArray();

        $this->torrent = $response->filter('.control.is-expanded a')->attr('href');

        // Gallery. Only for FC
        $gallery = $response->filter('.columns .column a img');
        if ($gallery->count()) {
            $this->gallery = collect($gallery->each(
                function ($image) {
                    return trim($image->attr('src'));
                }
            ))->reject(function ($value) {
                return empty($value);
            })->unique()->toArray();
        }

        return $this;
    }

    public function __set($name, $value)
    {
        $this->data->{$name} = is_string($value) ? trim($value) : $value;
    }

    public function getDownloadLink(): string
    {
        return config('jav.onejav.base_uri') . trim($this->torrent, '/');
    }

    public function getCover(
        bool $showAdult = false,
        int $width = 600,
        int $height = 600
    ): string {
        return $showAdult ? $this->cover : 'https://placehold.co/' . $width . 'x' . $height;
    }
}
