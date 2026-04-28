<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MobileNetwork extends Model
{
    protected $fillable = [
        'mcc',
        'mnc',
        'plmn',
        'name',
        'country_code',
        'country',
        'tadig',
    ];

    public static function lookup(?string $code): ?self
    {
        if (blank($code)) {
            return null;
        }

        $code = trim($code);
        $digits = preg_replace('/\D+/', '', $code);

        if (strlen((string) $digits) >= 5) {
            return static::where('plmn', $digits)->first()
                ?? static::where('mcc', substr($digits, 0, 3))
                    ->where('mnc', ltrim(substr($digits, 3), '0') ?: '0')
                    ->first();
        }

        return static::where('tadig', Str::upper($code))->first();
    }

    public static function splitMccMnc(?string $code): array
    {
        $digits = preg_replace('/\D+/', '', (string) $code);

        if (strlen($digits) < 5) {
            return [null, null, null];
        }

        return [substr($digits, 0, 3), substr($digits, 3), $digits];
    }
}
