<?php

namespace Modules\JAV\Services\Xcity;

use Symfony\Component\DomCrawler\Crawler;

class XcityDetailAdapter
{
    private const BASE_URL = 'https://xxx.xcity.jp';

    public function __construct(
        private readonly Crawler $dom
    ) {}

    /**
     * @return array{
     *     name: ?string,
     *     cover_image: ?string,
     *     fields: array<string, string>,
     *     raw_fields: array<string, string>
     * }
     */
    public function profile(): array
    {
        $fields = [];
        $rawFields = [];

        $this->dom->filter('#avidolDetails dl.profile dd')->each(function (Crawler $dd) use (&$fields, &$rawFields) {
            $labelNode = $dd->filter('span.koumoku');
            if ($labelNode->count() === 0) {
                return;
            }

            $label = trim($labelNode->text(''));
            if ($label === '' || str_contains($label, '★')) {
                return;
            }

            $value = $this->extractValue($dd);
            if ($value === '') {
                return;
            }

            $rawFields[$label] = $value;

            $key = $this->mapLabelToKey($label);
            $fields[$key] = $value;
        });

        $name = null;
        if ($this->dom->filter('#avidolDetails h1')->count() > 0) {
            $name = trim($this->dom->filter('#avidolDetails h1')->first()->text(''));
        }

        $cover = null;
        if ($this->dom->filter('#avidolDetails .photo img')->count() > 0) {
            $cover = $this->toAbsoluteUrl((string) $this->dom->filter('#avidolDetails .photo img')->first()->attr('src'));
        }

        return [
            'name' => $name !== '' ? $name : null,
            'cover_image' => $cover !== '' ? $cover : null,
            'fields' => $fields,
            'raw_fields' => $rawFields,
        ];
    }

    private function extractValue(Crawler $dd): string
    {
        $html = $dd->html('');
        if ($html === '') {
            return '';
        }

        $withoutLabel = preg_replace('/<span[^>]*class="koumoku"[^>]*>.*?<\/span>/u', '', $html, 1);
        $value = html_entity_decode(strip_tags((string) $withoutLabel), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function mapLabelToKey(string $label): string
    {
        $normalized = mb_strtolower(trim($label));
        $normalized = str_replace([' ', '_', '-'], '', $normalized);

        return match ($normalized) {
            'dateofbirth', '生年月日' => 'birth_date',
            'bloodtype', '血液型' => 'blood_type',
            'cityofborn', 'cityofbirth', '出身地' => 'city_of_birth',
            'height', '身長' => 'height',
            'size', 'スリーサイズ' => 'size',
            'hobby', '趣味' => 'hobby',
            'specialskill', 'specialskills', '特技' => 'special_skill',
            'other', 'その他' => 'other',
            default => 'extra_'.substr(sha1($label), 0, 12),
        };
    }

    private function toAbsoluteUrl(string $url): string
    {
        if ($url === '') {
            return $url;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        if (str_starts_with($url, '/')) {
            return self::BASE_URL.$url;
        }

        return self::BASE_URL.'/'.ltrim($url, '/');
    }
}
