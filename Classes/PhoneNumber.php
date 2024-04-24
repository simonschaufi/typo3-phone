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

namespace SimonSchaufi\TYPO3Phone;

use libphonenumber\NumberParseException as libNumberParseException;
use libphonenumber\PhoneNumber as libPhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use SimonSchaufi\TYPO3Phone\Exceptions\CountryCodeException;
use SimonSchaufi\TYPO3Phone\Exceptions\NumberFormatException;
use SimonSchaufi\TYPO3Phone\Exceptions\NumberParseException;
use SimonSchaufi\TYPO3Phone\Traits\ParsesCountries;
use SimonSchaufi\TYPO3Phone\Traits\ParsesFormats;
use SimonSchaufi\TYPO3Phone\Traits\ParsesTypes;

/**
 * @see https://github.com/Propaganistas/Laravel-Phone/blob/master/src/PhoneNumber.php
 */
class PhoneNumber implements \JsonSerializable, \Serializable
{
    use ParsesCountries;
    use ParsesFormats;
    use ParsesTypes;

    /**
     * The provided phone number.
     */
    protected string $number;

    /**
     * The provided phone country.
     */
    protected array $countries = [];

    /**
     * The detected phone country.
     *
     * @var string
     */
    protected $country;

    /**
     * Whether to allow lenient checks (i.e. landline numbers without area codes).
     */
    protected bool $lenient = false;

    protected PhoneNumberUtil $lib;

    public function __construct(string $number)
    {
        $this->number = $number;
        $this->lib = PhoneNumberUtil::getInstance();
    }

    /**
     * Create a phone instance.
     *
     * @param string       $number
     * @param string|array $country
     * @return static
     */
    public static function make(string $number, $country = []): PhoneNumber
    {
        $instance = new static($number);

        return $instance->ofCountry($country);
    }

    /**
     * Set the country to which the phone number belongs to.
     *
     * @param string|array $country
     * @return static
     */
    public function ofCountry($country): PhoneNumber
    {
        $countries = is_array($country) ? $country : func_get_args();

        $instance = clone $this;
        $instance->countries = array_unique(
            array_merge($instance->countries, static::parseCountries($countries))
        );

        return $instance;
    }

    /**
     * Format the phone number in international format.
     *
     * @throws NumberFormatException
     * @throws NumberParseException
     * @throws libNumberParseException
     */
    public function formatInternational(): string
    {
        return $this->format(PhoneNumberFormat::INTERNATIONAL);
    }

    /**
     * Format the phone number in national format.
     *
     * @throws NumberFormatException
     * @throws NumberParseException
     * @throws libNumberParseException
     */
    public function formatNational(): string
    {
        return $this->format(PhoneNumberFormat::NATIONAL);
    }

    /**
     * Format the phone number in E164 format.
     *
     * @throws NumberFormatException
     * @throws NumberParseException
     * @throws libNumberParseException
     */
    public function formatE164(): string
    {
        return $this->format(PhoneNumberFormat::E164);
    }

    /**
     * Format the phone number in RFC3966 format.
     *
     * @throws NumberFormatException
     * @throws NumberParseException
     * @throws libNumberParseException
     */
    public function formatRFC3966(): string
    {
        return $this->format(PhoneNumberFormat::RFC3966);
    }

    /**
     * Format the phone number in a given format.
     *
     * @param string|int $format
     *
     * @return string
     * @throws NumberFormatException
     * @throws NumberParseException
     * @throws libNumberParseException
     */
    public function format($format): string
    {
        $parsedFormat = static::parseFormat($format);

        if (is_null($parsedFormat)) {
            throw NumberFormatException::invalid($format);
        }

        return $this->lib->format(
            $this->getPhoneNumberInstance(),
            $parsedFormat
        );
    }

    /**
     * Format the phone number in a way that it can be dialled from the provided country.
     *
     * @throws CountryCodeException
     * @throws libNumberParseException
     */
    public function formatForCountry(string $country): string
    {
        if (! static::isValidCountryCode($country)) {
            throw CountryCodeException::invalid($country);
        }

        return $this->lib->formatOutOfCountryCallingNumber(
            $this->getPhoneNumberInstance(),
            $country
        );
    }

    /**
     * Format the phone number in a way that it can be dialled from the provided country using a cellphone.
     *
     * @throws CountryCodeException
     * @throws libNumberParseException
     */
    public function formatForMobileDialingInCountry(string $country, bool $removeFormatting = false): string
    {
        if (! static::isValidCountryCode($country)) {
            throw CountryCodeException::invalid($country);
        }

        return $this->lib->formatNumberForMobileDialing(
            $this->getPhoneNumberInstance(),
            $country,
            $removeFormatting
        );
    }

    /**
     * Get the phone number's country.
     *
     * @throws NumberParseException
     * @throws libNumberParseException
     */
    public function getCountry(): string
    {
        if (! $this->country) {
            $this->country = $this->filterValidCountry($this->countries);
        }

        return $this->country;
    }

    /**
     * Check if the phone number is of (a) given country(ies).
     *
     * @param string|array $country
     *
     * @return bool
     * @throws NumberParseException
     * @throws libNumberParseException
     */
    public function isOfCountry($country): bool
    {
        $countries = static::parseCountries($country);

        return in_array($this->getCountry(), $countries, true);
    }

    /**
     * Filter the provided countries to the one that is valid for the number.
     *
     * @throws NumberParseException
     * @throws libNumberParseException
     */
    protected function filterValidCountry(array $countries): string
    {
        $countries = array_filter($countries, function ($country) {
            try {
                $instance = $this->lib->parse($this->number, $country);

                return $this->lenient
                    ? $this->lib->isPossibleNumber($instance, $country)
                    : $this->lib->isValidNumberForRegion($instance, $country);
            } catch (libNumberParseException $e) {
                return false;
            }
        });

        $result = $countries[0] ?? null;

        // If we got a new result, return it.
        if ($result) {
            return $result;
        }

        // Last resort: try to detect it from an international number.
        if ($this->numberLooksInternational()) {
            $countries[] = null;
        }

        foreach ($countries as $country) {
            $instance = $this->lib->parse($this->number, $country);

            if ($this->lib->isValidNumber($instance)) {
                return $this->lib->getRegionCodeForNumber($instance);
            }
        }

        if ($countries = array_filter($countries)) {
            throw NumberParseException::countryMismatch($this->number, $countries);
        }

        throw NumberParseException::countryRequired($this->number);
    }

    /**
     * Get the phone number's type.
     *
     * @param bool $asConstant
     *
     * @return string|int|null
     * @throws libNumberParseException
     */
    public function getType(bool $asConstant = false)
    {
        $type = $this->lib->getNumberType($this->getPhoneNumberInstance());

        if ($asConstant) {
            return $type;
        }

        $stringType = static::parseTypesAsStrings($type)[0];

        return $stringType ? strtolower($stringType) : null;
    }

    /**
     * Check if the phone number is of (a) given type(s).
     *
     * @param string|int $type
     *
     * @return bool
     * @throws libNumberParseException
     */
    public function isOfType($type): bool
    {
        $types = static::parseTypes($type);

        // Add the unsure type when applicable.
        if (array_intersect([PhoneNumberType::FIXED_LINE, PhoneNumberType::MOBILE], $types)) {
            $types[] = PhoneNumberType::FIXED_LINE_OR_MOBILE;
        }

        return in_array($this->getType(true), $types, true);
    }

    /**
     * Get the raw provided number.
     */
    public function getRawNumber(): string
    {
        return $this->number;
    }

    /**
     * Get the PhoneNumber instance of the current number.
     *
     * @throws NumberParseException
     * @throws libNumberParseException
     */
    public function getPhoneNumberInstance(): libPhoneNumber
    {
        return $this->lib->parse($this->number, $this->getCountry());
    }

    /**
     * Determine whether the phone number seems to be in international format.
     */
    protected function numberLooksInternational(): bool
    {
        return str_starts_with($this->number, '+');
    }

    /**
     * Enable lenient number parsing.
     */
    public function lenient(): self
    {
        $this->lenient = true;

        return $this;
    }

    /**
     * Convert the phone instance to JSON.
     *
     * @throws NumberFormatException
     * @throws libNumberParseException
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the phone instance into something JSON serializable.
     *
     * @return string
     * @throws NumberFormatException
     * @throws libNumberParseException
     */
    public function jsonSerialize(): string
    {
        return $this->formatE164();
    }

    /**
     * Convert the phone instance into a string representation.
     *
     * @return string
     * @throws NumberFormatException
     * @throws libNumberParseException
     */
    public function serialize()
    {
        return $this->formatE164();
    }

    /**
     * Reconstructs the phone instance from a string representation.
     *
     * @param string $serialized
     *
     * @throws NumberParseException
     * @throws libNumberParseException
     */
    public function unserialize($serialized)
    {
        $this->lib = PhoneNumberUtil::getInstance();
        $this->number = $serialized;
        $this->country = $this->lib->getRegionCodeForNumber($this->getPhoneNumberInstance());
    }

    public function __serialize()
    {
        return ['number' => $this->formatE164()];
    }

    public function __unserialize(array $data)
    {
        $this->number = $data['number'];
    }

    /**
     * Convert the phone instance to a formatted number.
     *
     * @return string
     */
    public function __toString()
    {
        // Formatting the phone number could throw an exception, but __toString() doesn't cope well with that.
        // Let's just return the original number in that case.
        try {
            return $this->formatE164();
        } catch (\Exception $exception) {
            return (string)$this->number;
        }
    }
}
