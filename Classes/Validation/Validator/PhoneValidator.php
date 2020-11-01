<?php

declare(strict_types=1);

namespace SimonSchaufi\TYPO3Phone\Validation\Validator;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use ReflectionException;
use SimonSchaufi\TYPO3Phone\Exceptions\InvalidParameterException;
use SimonSchaufi\TYPO3Phone\PhoneNumber;
use SimonSchaufi\TYPO3Phone\Traits\ParsesCountries;
use SimonSchaufi\TYPO3Phone\Traits\ParsesTypes;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class PhoneValidator extends AbstractValidator
{
    use ParsesCountries;
    use ParsesTypes;

    /**
     * @var PhoneNumberUtil
     */
    protected $lib;

    /**
     * @var array
     */
    protected $supportedOptions = [
        'countries' => [
            [],
            'Array of countries',
            'array'
        ],
    ];

    /**
     * PhoneValidator constructor.
     *
     * @param array $options
     *
     * @throws InvalidValidationOptionsException
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->lib = PhoneNumberUtil::getInstance();
    }

    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to result.
     *
     * @param mixed $value
     *
     * @return bool
     * @throws ReflectionException
     * @throws InvalidParameterException
     */
    protected function isValid($value)
    {
        $parameters = $this->getOptions();

        [
            $countries,
            $types,
            $detect,
            $lenient
        ] = $this->extractParameters($parameters['countries']);

        // A "null" country is prepended:
        // 1. In case of auto-detection to have the validation run first without supplying a country.
        // 2. In case of lenient validation without provided countries; we still might have some luck...
        if ($detect || ($lenient && empty($countries))) {
            array_unshift($countries, null);
        }

        foreach ($countries as $country) {
            try {
                // Parsing the phone number also validates the country, so no need to do this explicitly.
                // It'll throw a PhoneCountryException upon failure.
                $phoneNumber = PhoneNumber::make($value, $country);

                // Type validation.
                if (!empty($types) && !$phoneNumber->isOfType($types)) {
                    continue;
                }

                $lenientPhoneNumber = $phoneNumber->lenient()->getPhoneNumberInstance();

                // Lenient validation.
                if ($lenient && $this->lib->isPossibleNumber($lenientPhoneNumber, $country)) {
                    return true;
                }

                $phoneNumberInstance = $phoneNumber->getPhoneNumberInstance();

                // Country detection.
                if ($detect && $this->lib->isValidNumber($phoneNumberInstance)) {
                    return true;
                }

                // Default number+country validation.
                if ($this->lib->isValidNumberForRegion($phoneNumberInstance, $country)) {
                    return true;
                }
            } catch (NumberParseException $e) {
                continue;
            }
        }

        $this->addError(
            LocalizationUtility::translate(
                'error_invalid_number_format',
                'Typo3Phone'
            ),
            1552843864
        );
        return false;
    }

    /**
     * Parse and extract parameters in the appropriate validation arguments.
     *
     * @param array $parameters
     *
     * @return array
     * @throws InvalidParameterException
     */
    protected function extractParameters(array $parameters): array
    {
        $countries = static::parseCountries($parameters);
        $types = static::parseTypes($parameters);

        // Force developers to write proper code.
        // Since the static parsers return a validated array with preserved keys, we can safely diff against the keys.
        // Unfortunately we can't use $collection->diffKeys() as it's not available yet in earlier 5.* versions.
        $leftovers = array_diff_key($parameters, $types, $countries);
        $leftovers = array_diff($leftovers, ['AUTO', 'LENIENT']);

        if (!empty($leftovers)) {
            throw InvalidParameterException::parameters($leftovers);
        }

        return [
            $countries,
            $types,
            in_array('AUTO', $parameters, true),
            in_array('LENIENT', $parameters, true),
        ];
    }
}
