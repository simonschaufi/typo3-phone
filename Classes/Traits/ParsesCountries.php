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

use League\ISO3166\ISO3166;
use SimonSchaufi\TYPO3Support\Collection;

trait ParsesCountries
{
    /**
     * Determine whether the given country code is valid.
     *
     * @param string $country
     * @return bool
     */
    public static function isValidCountryCode(string $country): bool
    {
        $iso3166 = new ISO3166();

        try {
            $iso3166->alpha2($country);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Parse the provided phone countries to a valid array.
     *
     * @param string|array $countries
     * @return array
     */
    protected static function parseCountries($countries): array
    {
        return Collection::make(is_array($countries) ? $countries : func_get_args())
            ->map(function ($country) {
                return strtoupper($country ?? '');
            })
            ->filter(function ($value) {
                return static::isValidCountryCode($value);
            })->toArray();
    }
}
