<?php

declare(strict_types=1);

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
    protected static $types;

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
                if (is_numeric($type) && in_array($type, static::$types, true)) {
                    return (int)$type;
                }

                // Otherwise we'll assume the type is the constant's name.
                return Arr::get(static::$types, strtoupper($type));
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
                static::$types,
                static::parseTypes($types)
            )
        );
    }

    /**
     * Load all available formats once.
     */
    private static function loadTypes(): void
    {
        if (! static::$types) {
            static::$types = with(new ReflectionClass(PhoneNumberType::class))->getConstants();
        }
    }
}
