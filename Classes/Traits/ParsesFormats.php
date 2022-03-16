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

use libphonenumber\PhoneNumberFormat;
use ReflectionClass;
use SimonSchaufi\TYPO3Support\Arr;

trait ParsesFormats
{
    /**
     * Array of available phone formats.
     *
     * @var array
     */
    protected static $formats;

    /**
     * Determine whether the given format is valid.
     *
     * @param string|int $format
     * @return bool
     */
    public static function isValidFormat($format): bool
    {
        return ! is_null(static::parseFormat($format));
    }

    /**
     * Parse a phone format.
     *
     * @param string|int $format
     * @return int|null
     */
    protected static function parseFormat($format): ?int
    {
        static::loadFormats();

        // If the format equals a constant's value, just return it.
        if (in_array($format, static::$formats, true)) {
            return $format;
        }

        // Otherwise we'll assume the format is the constant's name.
        return Arr::get(static::$formats, strtoupper($format));
    }

    /**
     * Load all available formats once.
     */
    private static function loadFormats(): void
    {
        if (! static::$formats) {
            static::$formats = with(new ReflectionClass(PhoneNumberFormat::class))->getConstants();
        }
    }
}
