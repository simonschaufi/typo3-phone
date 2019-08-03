<?php
declare(strict_types=1);

namespace SimonSchaufi\TYPO3Phone\Traits;

use SimonSchaufi\TYPO3Support\Collection;
use Iso3166\Codes as ISO3166;

trait ParsesCountries
{
    /**
     * Determine whether the given country code is valid.
     *
     * @param string $country
     * @return bool
     */
    public static function isValidCountryCode($country): bool
    {
        return ISO3166::isValid($country);
    }

    /**
     * Parse the provided phone countries to a valid array.
     *
     * @param string|array $countries
     * @return array
     */
    protected function parseCountries($countries)
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
