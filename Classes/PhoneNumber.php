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
use libphonenumber\PhoneNumberFormat as libPhoneNumberFormat;
use libphonenumber\PhoneNumberType as libPhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use SimonSchaufi\TYPO3Phone\Aspects\PhoneNumberCountry;
use SimonSchaufi\TYPO3Phone\Aspects\PhoneNumberFormat;
use SimonSchaufi\TYPO3Phone\Aspects\PhoneNumberType;
use SimonSchaufi\TYPO3Phone\Exceptions\CountryCodeException;
use SimonSchaufi\TYPO3Phone\Exceptions\NumberFormatException;
use SimonSchaufi\TYPO3Phone\Exceptions\NumberParseException;
use SimonSchaufi\TYPO3Support\Arr;

class PhoneNumber implements \JsonSerializable, \Stringable
{
    protected array $countries = [];

    /**
     * Whether to allow lenient checks (i.e. landline numbers without area codes).
     */
    protected bool $lenient = false;

    final public function __construct(protected ?string $number, array|string|null $country = [])
    {
        $this->countries = Arr::wrap($country);
    }

    /**
     * Get the phone number's country.
     */
    public function getCountry(): string|null
    {
        // Try to detect the country first from the number itself.
        try {
            return PhoneNumberUtil::getInstance()->getRegionCodeForNumber(
                PhoneNumberUtil::getInstance()->parse($this->number, 'ZZ')
            );
        } catch (libNumberParseException) {
        }

        // Only then iterate over the provided countries.
        $sanitizedCountries = PhoneNumberCountry::sanitize($this->countries);

        foreach ($sanitizedCountries as $country) {
            try {
                $libPhoneObject = PhoneNumberUtil::getInstance()->parse($this->number, $country);
            } catch (libNumberParseException) {
                continue;
            }

            if ($this->lenient) {
                if (PhoneNumberUtil::getInstance()->isPossibleNumber($libPhoneObject, $country)) {
                    return strtoupper((string)$country);
                }

                continue;
            }

            if (PhoneNumberUtil::getInstance()->isValidNumberForRegion($libPhoneObject, $country)) {
                return PhoneNumberUtil::getInstance()->getRegionCodeForNumber($libPhoneObject);
            }
        }

        return null;
    }

    /**
     * Check if the phone number is of (a) given country(ies).
     */
    public function isOfCountry(array|string $country): bool
    {
        $countries = PhoneNumberCountry::sanitize(Arr::wrap($country));

        $instance = clone $this;
        $instance->countries = $countries;

        return in_array($instance->getCountry(), $countries, true);
    }

    /**
     * Get the phone number's type.
     *
     * @throws libNumberParseException
     */
    public function getType(bool $asValue = false): int|string
    {
        $type = PhoneNumberUtil::getInstance()->getNumberType($this->toLibPhoneObject());

        return $asValue ? $type : PhoneNumberType::getHumanReadableName($type);
    }

    /**
     * Check if the phone number is of (a) given type(s).
     *
     * @throws libNumberParseException
     */
    public function isOfType(int|string|array $type): bool
    {
        $types = PhoneNumberType::sanitize(Arr::wrap($type));

        // Add the unsure type when applicable.
        if (array_intersect([libPhoneNumberType::FIXED_LINE, libPhoneNumberType::MOBILE], $types) !== []) {
            $types[] = libPhoneNumberType::FIXED_LINE_OR_MOBILE;
        }

        return in_array($this->getType(true), $types, true);
    }

    /**
     * Format the phone number in a given format.
     *
     * @throws NumberFormatException
     * @throws NumberParseException
     * @throws libNumberParseException
     */
    public function format(string|int $format): string
    {
        $sanitizedFormat = PhoneNumberFormat::sanitize($format);

        if (is_null($sanitizedFormat)) {
            throw NumberFormatException::invalid($format);
        }

        return PhoneNumberUtil::getInstance()->format(
            $this->toLibPhoneObject(),
            $sanitizedFormat
        );
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
        return $this->format(libPhoneNumberFormat::INTERNATIONAL);
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
        return $this->format(libPhoneNumberFormat::NATIONAL);
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
        return $this->format(libPhoneNumberFormat::E164);
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
        return $this->format(libPhoneNumberFormat::RFC3966);
    }

    /**
     * Format the phone number in a way that it can be dialled from the provided country.
     *
     * @throws CountryCodeException
     * @throws libNumberParseException
     */
    public function formatForCountry(string $country): string
    {
        if (! PhoneNumberCountry::isValid($country)) {
            throw CountryCodeException::invalid($country);
        }

        return PhoneNumberUtil::getInstance()->formatOutOfCountryCallingNumber(
            $this->toLibPhoneObject(),
            $country
        );
    }

    /**
     * Format the phone number in a way that it can be dialled from the provided country using a cellphone.
     *
     * @throws CountryCodeException
     * @throws libNumberParseException
     */
    public function formatForMobileDialingInCountry(string $country, $withFormatting = false): string
    {
        if (! PhoneNumberCountry::isValid($country)) {
            throw CountryCodeException::invalid($country);
        }

        return PhoneNumberUtil::getInstance()->formatNumberForMobileDialing(
            $this->toLibPhoneObject(),
            $country,
            $withFormatting
        );
    }

    public function isValid(): bool
    {
        try {
            if ($this->lenient) {
                return PhoneNumberUtil::getInstance()->isPossibleNumber(
                    $this->toLibPhoneObject()
                );
            }

            return PhoneNumberUtil::getInstance()->isValidNumberForRegion(
                $this->toLibPhoneObject(),
                $this->getCountry(),
            );
        } catch (NumberParseException) {
            return false;
        }
    }

    public function lenient(bool $enable = true): self
    {
        $this->lenient = $enable;

        return $this;
    }

    public function equals($number, $country = null): bool
    {
        try {
            if (! $number instanceof static) {
                $number = new static($number, $country);
            }

            return $this->formatE164() === $number->formatE164();
        } catch (NumberParseException) {
            return false;
        }
    }

    public function notEquals($number, $country = null): bool
    {
        return ! $this->equals($number, $country);
    }

    public function getRawNumber(): string
    {
        return $this->number;
    }

    public function toLibPhoneObject(): ?\libphonenumber\PhoneNumber
    {
        try {
            return PhoneNumberUtil::getInstance()->parse($this->number, $this->getCountry());
        } catch (libNumberParseException) {
            $this->countries === []
                ? throw NumberParseException::countryRequired($this->number)
                : throw NumberParseException::countryMismatch($this->number, $this->countries);
        }
    }

    /**
     * Convert the phone instance into something JSON serializable.
     *
     * @throws NumberFormatException
     * @throws libNumberParseException
     */
    public function jsonSerialize(): mixed
    {
        return $this->formatE164();
    }

    public function __serialize(): array
    {
        return ['number' => $this->formatE164()];
    }

    public function __unserialize(array $serialized): void
    {
        $this->number = $serialized['number'];
    }

    public function __toString(): string
    {
        // Formatting the phone number could throw an exception,
        // but __toString() doesn't cope well with that.
        // Let's return the original number in that case.
        try {
            return $this->formatE164();
        } catch (\Exception) {
            return (string)$this->number;
        }
    }
}
