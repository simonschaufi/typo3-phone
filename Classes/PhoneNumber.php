<?php

declare(strict_types=1);

namespace SimonSchaufi\TYPO3Phone;

use Exception;
use JsonSerializable;
use libphonenumber\NumberParseException as libNumberParseException;
use libphonenumber\PhoneNumber as libPhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use ReflectionException;
use Serializable;
use SimonSchaufi\TYPO3Phone\Exceptions\CountryCodeException;
use SimonSchaufi\TYPO3Phone\Exceptions\NumberFormatException;
use SimonSchaufi\TYPO3Phone\Exceptions\NumberParseException;
use SimonSchaufi\TYPO3Phone\Traits\ParsesCountries;
use SimonSchaufi\TYPO3Phone\Traits\ParsesFormats;
use SimonSchaufi\TYPO3Phone\Traits\ParsesTypes;
use TYPO3\CMS\Core\Utility\StringUtility;

class PhoneNumber implements JsonSerializable, Serializable
{
    use ParsesCountries;
    use ParsesFormats;
    use ParsesTypes;

    /**
     * The provided phone number.
     *
     * @var string
     */
    protected $number;

    /**
     * The provided phone country.
     *
     * @var array
     */
    protected $countries = [];

    /**
     * The detected phone country.
     *
     * @var string
     */
    protected $country;

    /**
     * Whether to allow lenient checks (i.e. landline numbers without area codes).
     *
     * @var bool
     */
    protected $lenient = false;

    /**
     * @var PhoneNumberUtil
     */
    protected $lib;

    /**
     * Phone constructor.
     *
     * @param string $number
     */
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
    public static function make(string $number, $country = [])
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
    public function ofCountry($country)
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
     * @return string
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
     * @return string
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
     * @return string
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
     * @return string
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
     * @param string $country
     *
     * @return string
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
     * @param string $country
     * @param bool $removeFormatting
     *
     * @return string
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
     * @return string
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
     * @param array $countries
     *
     * @return string
     * @throws NumberParseException
     * @throws libNumberParseException
     */
    protected function filterValidCountry(array $countries): string
    {
        $countries = array_filter($countries, function ($country) {
            $instance = $this->lib->parse($this->number, $country);

            return $this->lenient
                ? $this->lib->isPossibleNumber($instance, $country)
                : $this->lib->isValidNumberForRegion($instance, $country);
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

        throw NumberParseException::countryRequired($this->number);
    }

    /**
     * Get the phone number's type.
     *
     * @param bool $asConstant
     *
     * @return string|int|null
     * @throws ReflectionException
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
     * @throws ReflectionException
     * @throws libNumberParseException
     */
    public function isOfType($type): bool
    {
        $types = static::parseTypes($type);

        return in_array($this->getType(true), $types, true);
    }

    /**
     * Get the PhoneNumber instance of the current number.
     *
     * @return libPhoneNumber
     * @throws NumberParseException
     * @throws libNumberParseException
     */
    public function getPhoneNumberInstance(): libPhoneNumber
    {
        return $this->lib->parse($this->number, $this->getCountry());
    }

    /**
     * Determine whether the phone number seems to be in international format.
     *
     * @return bool
     */
    protected function numberLooksInternational(): bool
    {
        return StringUtility::beginsWith($this->number, '+');
    }

    /**
     * Enable lenient number parsing.
     *
     * @return $this
     */
    public function lenient(): self
    {
        $this->lenient = true;

        return $this;
    }

    /**
     * Convert the phone instance to JSON.
     *
     * @param  int $options
     *
     * @return string
     * @throws NumberFormatException
     * @throws libNumberParseException
     */
    public function toJson($options = 0): string
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
    public function jsonSerialize()
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
        } catch (Exception $exception) {
            return (string)$this->number;
        }
    }
}
