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

namespace SimonSchaufi\TYPO3Phone\Traits;

use libphonenumber\PhoneNumberType;
use ReflectionClass;
use SimonSchaufi\TYPO3Support\Arr;
use SimonSchaufi\TYPO3Support\Collection;

trait ParsesTypes
{
    /**
     * Array of available phone types.
     *
     * @var array
     */
    protected static $resolvedTypes;

    /**
     * Determine whether the given type is valid.
     *
     * @param string $type
     *
     * @return bool
     */
    public static function isValidType($type): bool
    {
        return !empty(static::parseTypes($type));
    }

    /**
     * Parse a phone type into constant's value.
     *
     * @param string|array $types
     * @return array
     */
    protected static function parseTypes($types): array
    {
        static::loadTypes();

        return Collection::make(is_array($types) ? $types : func_get_args())
            ->map(function ($type) {
                // If the type equals a constant's value, just return it.
                if (is_numeric($type) && in_array($type, static::$resolvedTypes, true)) {
                    return (int)$type;
                }

                // Otherwise we'll assume the type is the constant's name.
                return Arr::get(static::$resolvedTypes, strtoupper($type));
            })
            ->reject(function ($value) {
                return is_null($value) || $value === false;
            })->toArray();
    }

    /**
     * Parse a phone type into its string representation.
     *
     * @param string|array $types
     * @return array
     */
    protected static function parseTypesAsStrings($types): array
    {
        static::loadTypes();

        return array_keys(
            array_intersect(
                static::$resolvedTypes,
                static::parseTypes($types)
            )
        );
    }

    /**
     * Load all available formats once.
     */
    private static function loadTypes(): void
    {
        if (! static::$resolvedTypes) {
            static::$resolvedTypes = with(new ReflectionClass(PhoneNumberType::class))->getConstants();
        }
    }
}
