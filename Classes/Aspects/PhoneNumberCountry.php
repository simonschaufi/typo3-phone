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

use libphonenumber\PhoneNumberUtil;
use SimonSchaufi\TYPO3Support\Collection;

class PhoneNumberCountry
{
    public static function all(): array
    {
        return array_map('strtoupper', PhoneNumberUtil::getInstance()->getSupportedRegions());
    }

    public static function isValid($code): bool
    {
        return $code !== null && in_array(strtoupper((string)$code), static::all(), true);
    }

    public static function sanitize($countries): string|array|null
    {
        $sanitized = Collection::make(is_array($countries) ? $countries : [$countries])
            ->filter(fn ($value): bool => static::isValid($value))->map(fn ($value): string => strtoupper((string)$value))->unique();

        return is_array($countries) ? $sanitized->toArray() : $sanitized->first();
    }
}
