<?php

declare(strict_types=1);

namespace SimonSchaufi\TYPO3Phone\Exceptions;

class CountryCodeException extends \Exception
{
    /**
     * Invalid country code static constructor.
     *
     * @param string $country
     * @return static
     */
    public static function invalid(string $country)
    {
        return new static('Invalid country code "' . $country . '".');
    }
}
