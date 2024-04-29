<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * (c) Simon Schaufelberger
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace SimonSchaufi\TYPO3Phone\Aspects;

use libphonenumber\PhoneNumberFormat as libPhoneNumberFormat;
use SimonSchaufi\TYPO3Support\Arr;
use SimonSchaufi\TYPO3Support\Collection;

class PhoneNumberFormat
{
    public static function all(): array
    {
        return (new \ReflectionClass(libPhoneNumberFormat::class))->getConstants();
    }

    public static function isValid($format): bool
    {
        return ! is_null($format) && in_array($format, static::all(), true);
    }

    public static function isValidName($format): bool
    {
        return ! is_null($format) && array_key_exists($format, static::all());
    }

    public static function getHumanReadableName($format): string|null
    {
        $name = array_search($format, static::all(), true);

        return $name ? strtolower($name) : null;
    }

    public static function sanitize($formats): int|array|null
    {
        $sanitized = Collection::make(is_array($formats) ? $formats : [$formats])
            ->map(fn($format) =>
                // If the format equals a constant's name, return its value.
                // Otherwise just return the value.
                Arr::get(static::all(), strtoupper((string)$format), $format))
            ->filter(fn($format): bool => static::isValid($format))->unique();

        return is_array($formats) ? $sanitized->toArray() : $sanitized->first();
    }
}
