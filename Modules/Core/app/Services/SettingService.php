<?php

namespace Modules\Core\Services;

use Modules\Core\Models\Setting;

class SettingService
{
    public function get(string $group, string $key, ?string $default = null): string
    {
        $setting = Setting::where('group', $group)
            ->where('key', $key)
            ->first();

        return $setting ? $setting->value : $default;
    }

    public function set(string $group, string $key, $value): self
    {
        Setting::updateOrCreate([
            'group' => $group,
            'key' => $key,
        ], ['value' => $value]);

        return $this;
    }
}
