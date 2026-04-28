<?php

namespace App\Services;

use App\Models\AppSetting;

class AppSettings
{
    public function get(string $key, mixed $default = null): mixed
    {
        return AppSetting::getValue($key, $default);
    }

    public function set(string $key, ?string $value, bool $encrypted = false): void
    {
        AppSetting::setValue($key, $value, $encrypted);
    }

    public function has(string $key): bool
    {
        return AppSetting::hasValue($key);
    }
}
