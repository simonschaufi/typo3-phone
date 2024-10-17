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

use libphonenumber\PhoneNumberType as libPhoneNumberType;
use SimonSchaufi\TYPO3Support\Arr;
use SimonSchaufi\TYPO3Support\Collection;

class PhoneNumberType
{
    public static function all(): array
    {
        return (new \ReflectionClass(libPhoneNumberType::class))->getConstants();
    }

    public static function isValid($type): bool
    {
        return $type !== null && in_array($type, static::all(), true);
    }

    public static function isValidName($type): bool
    {
        return $type !== null && array_key_exists($type, static::all());
    }

    public static function getHumanReadableName($type): string|null
    {
        $name = array_search($type, static::all(), true);

        return $name ? strtolower($name) : null;
    }

    public static function sanitize($types): int|array|null
    {
        $sanitized = Collection::make(is_array($types) ? $types : [$types])
            ->map(fn ($format)
                // If the type equals a constant's name, return its value.
                // Otherwise just return the value.
                => Arr::get(static::all(), strtoupper((string)$format), $format))
            ->filter(fn ($format): bool => static::isValid($format))->unique();

        return is_array($types) ? $sanitized->toArray() : $sanitized->first();
    }
}
