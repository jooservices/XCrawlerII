<?php

namespace Modules\Jav\Dto\MissAv;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Jav\Dto\BaseDto;
use stdClass;

/**
 * @property string $meta_title
 * @property string $cover
 */
class ItemDetailDto extends BaseDto
{
    final public function transform(mixed $response): static
    {
        $this->data = new stdClass();
        $this->meta_title = $response->filter('title')->text();
        $response->filter('.space-y-2 .text-secondary')->each(function ($node) {
            $data = explode(':', trim($node->text()));
            $label = Str::slug($data[0], '_');
            $value = trim($data[1]);
            if ($label === 'release_date') {
                $value = Carbon::createFromFormat('Y-m-d', $value);
            }

            if (
                in_array(
                    $label,
                    ['genre', 'actress', 'actor', 'director', 'studio', 'label']
                )
            ) {
                $value = explode(',', $value);
                $value = array_map(static function ($value) {
                    return trim($value);
                }, $value);
            }

            $this->{$label} = $value;
        });

        $this->cover = trim(
            $response->filter('video.player')->attr('data-poster')
        );

        return $this;
    }
}
