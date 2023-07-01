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

namespace SimonSchaufi\TYPO3Phone\Validation\Validator;

use libphonenumber\NumberParseException;
use SimonSchaufi\TYPO3Phone\Aspects\PhoneNumberCountry;
use SimonSchaufi\TYPO3Phone\Aspects\PhoneNumberType;
use SimonSchaufi\TYPO3Phone\Exceptions\InvalidParameterException;
use SimonSchaufi\TYPO3Phone\PhoneNumber;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class PhoneValidator extends AbstractValidator
{
    /**
     * This contains the supported options, their default values, descriptions and types.
     *
     * @var array
     */
    protected $supportedOptions = [
        'countries' => [
            [],
            'Array of countries',
            'array',
        ],
        'types' => [
            [],
            'Array of phone number types',
            'array',
        ],
        'international' => [
            false,
            '',
            'boolean',
        ],
        'lenient' => [
            false,
            '',
            'boolean',
        ],
    ];

    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to result.
     *
     * @param mixed $value
     *
     * @return bool
     * @throws InvalidParameterException
     */
    protected function isValid(mixed $value): void
    {
        $parameters = $this->getOptions();

        $countries = PhoneNumberCountry::sanitize([
            ...$parameters['countries'],
        ]);

        $types = PhoneNumberType::sanitize($parameters['types']);

        try {
            $phone = (new PhoneNumber($value, $countries))->lenient($parameters['lenient']);

            // Is the country within the allowed list (if applicable)?
            if (!$parameters['international'] && !empty($countries) && !$phone->isOfCountry($countries)) {
                $this->invalid();
                return;
            }

            // Is the type within the allowed list (if applicable)?
            if (!empty($types) && !$phone->isOfType($types)) {
                $this->invalid();
                return;
            }

            if (!$phone->isValid()) {
                $this->invalid();
            }
        } catch (NumberParseException $e) {
            $this->invalid();
        }
    }

    protected function invalid(): void
    {
        $this->addError(
            $this->translateErrorMessage(
                'error_invalid_number_format',
                'Typo3Phone'
            ),
            1552843864
        );
    }
}
