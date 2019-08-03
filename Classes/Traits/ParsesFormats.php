<?php
declare(strict_types=1);

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
     * @param string $format
     * @return bool
     * @throws \ReflectionException
     */
    public static function isValidFormat($format)
    {
        return ! is_null(static::parseFormat($format));
    }

    /**
     * Parse a phone format.
     *
     * @param string $format
     * @return string
     * @throws \ReflectionException
     */
    protected static function parseFormat($format)
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
     *
     * @throws \ReflectionException
     */
    private static function loadFormats()
    {
        if (! static::$formats) {
            static::$formats = with(new ReflectionClass(PhoneNumberFormat::class))->getConstants();
        }
    }
}
