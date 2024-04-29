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

namespace SimonSchaufi\TYPO3Phone\Exceptions;

use libphonenumber\NumberParseException as libNumberParseException;

final class NumberParseException extends libNumberParseException
{
    private ?string $number = '';

    private array $countries = [];

    public static function countryRequired(?string $number): NumberParseException
    {
        $exception = new self(
            libNumberParseException::INVALID_COUNTRY_CODE,
            'Number requires a country to be specified.'
        );

        $exception->number = $number;

        return $exception;
    }

    public static function countryMismatch(string $number, array|string $countries): NumberParseException
    {
        $countries = array_filter(is_array($countries) ? $countries : [$countries]);

        $exception = new self(
            libNumberParseException::INVALID_COUNTRY_CODE,
            'Number does not match the provided ' . (count($countries) === 1 ? 'country' : 'countries') . '.'
        );

        $exception->number = $number;
        $exception->countries = $countries;

        return $exception;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getCountries(): array
    {
        return $this->countries;
    }
}
