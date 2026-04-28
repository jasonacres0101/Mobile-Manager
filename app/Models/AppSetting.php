<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'encrypted',
    ];

    protected function casts(): array
    {
        return [
            'encrypted' => 'boolean',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting || $setting->value === null || $setting->value === '') {
            return $default;
        }

        return $setting->encrypted ? Crypt::decryptString($setting->value) : $setting->value;
    }

    public static function setValue(string $key, ?string $value, bool $encrypted = false): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value !== null && $value !== ''
                    ? ($encrypted ? Crypt::encryptString($value) : $value)
                    : null,
                'encrypted' => $encrypted,
            ],
        );
    }

    public static function hasValue(string $key): bool
    {
        return filled(static::getValue($key));
    }
}
