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

namespace SimonSchaufi\TYPO3Phone\Tests\Unit;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use PHPUnit\Framework\Attributes\Test;
use SimonSchaufi\TYPO3Phone\Exceptions\CountryCodeException;
use SimonSchaufi\TYPO3Phone\Exceptions\NumberFormatException;
use SimonSchaufi\TYPO3Phone\Exceptions\NumberParseException;
use SimonSchaufi\TYPO3Phone\PhoneNumber;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PhoneNumberTest extends UnitTestCase
{
    #[Test]
    public function it_constructs_without_country(): void
    {
        $object = new PhoneNumber('012345678');
        self::assertInstanceOf(PhoneNumber::class, $object);
    }

    #[Test]
    public function it_constructs_with_string_country(): void
    {
        $object = new PhoneNumber('012345678', 'BE');
        self::assertInstanceOf(PhoneNumber::class, $object);
    }

    #[Test]
    public function it_constructs_with_array_country(): void
    {
        $object = new PhoneNumber('012345678', ['BE', 'NL']);
        self::assertInstanceOf(PhoneNumber::class, $object);
    }

    #[Test]
    public function it_constructs_with_null_country(): void
    {
        $object = new PhoneNumber('012345678', null);
        self::assertInstanceOf(PhoneNumber::class, $object);
    }

    #[Test]
    public function it_returns_the_raw_number(): void
    {
        $object = new PhoneNumber('012 34 56 78');
        self::assertEquals('012 34 56 78', $object->getRawNumber());
    }

    #[Test]
    public function it_returns_true_when_checking_correct_validity(): void
    {
        $object = new PhoneNumber('+3212345678');
        self::assertTrue($object->isValid());

        $object = new PhoneNumber('012345678', 'BE');
        self::assertTrue($object->isValid());

        $object = new PhoneNumber('012345678', ['NL', 'BE', 'FR']);
        self::assertTrue($object->isValid());
    }

    #[Test]
    public function it_returns_true_when_checking_correct_validity_with_wrong_country(): void
    {
        $object = new PhoneNumber('+3212345678', 'US');
        self::assertTrue($object->isValid());
    }

    #[Test]
    public function it_returns_false_when_checking_incorrect_validity(): void
    {
        $object = new PhoneNumber('012345678');
        self::assertFalse($object->isValid());

        $object = new PhoneNumber('012345678', 'NL');
        self::assertFalse($object->isValid());

        $object = new PhoneNumber('012345678', ['NL', 'FR']);
        self::assertFalse($object->isValid());

        $object = new PhoneNumber('foo');
        self::assertFalse($object->isValid());
    }

    #[Test]
    public function it_gets_the_country_for_an_international_number(): void
    {
        $object = new PhoneNumber('+3212345678');
        self::assertEquals('BE', $object->getCountry());
    }

    #[Test]
    public function it_gets_the_country_for_a_non_international_number(): void
    {
        $object = new PhoneNumber('012345678', ['NL', 'BE', 'FR']);
        self::assertEquals('BE', $object->getCountry());
    }

    #[Test]
    public function it_returns_null_when_country_is_not_found_for_a_non_international_number(): void
    {
        $object = new PhoneNumber('012345678', ['NL', 'FR']);
        self::assertNull($object->getCountry());
    }

    #[Test]
    public function it_ignores_invalid_countries(): void
    {
        $object = new PhoneNumber('012345678', ['BE', 'foo', 23]);
        self::assertEquals('BE', $object->getCountry());
    }

    #[Test]
    public function it_returns_true_when_checking_correct_country(): void
    {
        $object = new PhoneNumber('012345678');
        self::assertTrue($object->isOfCountry('BE'));

        $object = new PhoneNumber('+3212345678');
        self::assertTrue($object->isOfCountry('BE'));
    }

    #[Test]
    public function it_returns_false_when_checking_incorrect_country_or_null(): void
    {
        $object = new PhoneNumber('012345678');
        self::assertFalse($object->isOfCountry('US'));

        $object = new PhoneNumber('+3212345678');
        self::assertFalse($object->isOfCountry('US'));
    }

    #[Test]
    public function it_ignores_provided_countries_when_checking_country(): void
    {
        $object = new PhoneNumber('012345678', 'NL');
        self::assertTrue($object->isOfCountry('BE'));

        $object = new PhoneNumber('012345678', 'BE');
        self::assertFalse($object->isOfCountry('US'));
    }

    #[Test]
    public function it_checks_libphonenumber_specific_regions_as_country(): void
    {
        $object = new PhoneNumber('+247501234');
        self::assertTrue($object->isOfCountry('AC'));
        self::assertFalse($object->isOfCountry('US'));
    }

    #[Test]
    public function it_doesnt_throw_for_antarctica(): void
    {
        $object = new PhoneNumber('012345678', ['AQ', 'BE']);
        self::assertEquals('BE', $object->getCountry());
    }

    #[Test]
    public function it_returns_the_type(): void
    {
        $object = new PhoneNumber('012345678', 'BE');
        self::assertEquals('fixed_line', $object->getType());

        $object = new PhoneNumber('0470123456', 'BE');
        self::assertEquals('mobile', $object->getType());
    }

    #[Test]
    public function it_returns_the_type_value(): void
    {
        $object = new PhoneNumber('012345678', 'BE');
        self::assertEquals(PhoneNumberType::FIXED_LINE, $object->getType(true));

        $object = new PhoneNumber('0470123456', 'BE');
        self::assertEquals(PhoneNumberType::MOBILE, $object->getType(true));
    }

    #[Test]
    public function it_returns_true_when_checking_type_with_correct_name(): void
    {
        $object = new PhoneNumber('012345678', 'BE');
        self::assertTrue($object->isOfType('fixed_line'));
        self::assertFalse($object->isOfType('mobile'));

        $object = new PhoneNumber('0470123456', 'BE');
        self::assertFalse($object->isOfType('fixed_line'));
        self::assertTrue($object->isOfType('mobile'));
    }

    #[Test]
    public function it_returns_true_when_checking_type_with_correct_value(): void
    {
        $object = new PhoneNumber('012345678', 'BE');
        self::assertTrue($object->isOfType(PhoneNumberType::FIXED_LINE));
        self::assertFalse($object->isOfType(PhoneNumberType::MOBILE));

        $object = new PhoneNumber('0470123456', 'BE');
        self::assertFalse($object->isOfType(PhoneNumberType::FIXED_LINE));
        self::assertTrue($object->isOfType(PhoneNumberType::MOBILE));
    }

    #[Test]
    public function it_returns_false_when_checking_incorrect_type(): void
    {
        $object = new PhoneNumber('012345678', 'BE');
        self::assertFalse($object->isOfType('mobile'));
        self::assertFalse($object->isOfType(PhoneNumberType::MOBILE));
        self::assertFalse($object->isOfType('foo'));

        $object = new PhoneNumber('0470123456', 'BE');
        self::assertFalse($object->isOfType('fixed_line'));
        self::assertFalse($object->isOfType(PhoneNumberType::FIXED_LINE));
        self::assertFalse($object->isOfType('foo'));
    }

    #[Test]
    public function it_adds_the_unsure_type_when_checking_fixed_line_or_mobile(): void
    {
        // This number is of type FIXED_LINE_OR_MOBILE.
        // Without the unsure type, the following check would fail.
        $object = new PhoneNumber('8590332334', 'IN');
        self::assertTrue($object->isOfType('fixed_line'));
        self::assertTrue($object->isOfType('mobile'));
    }

    #[Test]
    public function it_formats_with_format_value(): void
    {
        $object = new PhoneNumber('+3212345678');
        self::assertEquals('012 34 56 78', $object->format(PhoneNumberFormat::NATIONAL));
    }

    #[Test]
    public function it_formats_with_format_name(): void
    {
        $object = new PhoneNumber('+3212345678');
        self::assertEquals('012 34 56 78', $object->format('national'));
    }

    #[Test]
    public function it_throws_an_exception_when_formatting_invalid_numbers(): void
    {
        $object = new PhoneNumber('012345678');

        $this->expectException(NumberParseException::class);
        $this->expectExceptionMessage('Number requires a country to be specified.');
        $object->format(PhoneNumberFormat::NATIONAL);
    }

    #[Test]
    public function it_throws_an_exception_for_invalid_formats(): void
    {
        $object = new PhoneNumber('+3212345678');

        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessage('foo');
        $object->format('foo');
    }

    #[Test]
    public function it_has_an_international_format_shortcut_method(): void
    {
        $object = new PhoneNumber('+3212345678');

        self::assertEquals(
            $object->format(PhoneNumberFormat::INTERNATIONAL),
            $object->formatInternational()
        );
    }

    #[Test]
    public function it_has_a_national_format_shortcut_method(): void
    {
        $object = new PhoneNumber('+3212345678');
        self::assertEquals(
            $object->format(PhoneNumberFormat::NATIONAL),
            $object->formatNational()
        );
    }

    #[Test]
    public function it_has_an_E164_format_shortcut_method(): void
    {
        $object = new PhoneNumber('012345678', 'BE');
        self::assertEquals(
            $object->format(PhoneNumberFormat::E164),
            $object->formatE164()
        );
    }

    #[Test]
    public function it_has_an_RFC3966_format_shortcut_method(): void
    {
        $object = new PhoneNumber('+3212345678');
        self::assertEquals(
            $object->format(PhoneNumberFormat::RFC3966),
            $object->formatRFC3966()
        );
    }

    #[Test]
    public function it_accepts_numbers_prefixed_with_something(): void
    {
        $object = new PhoneNumber('BE+3212345678');
        self::assertTrue($object->isValid());
        self::assertEquals('BE', $object->getCountry());
        self::assertEquals('012 34 56 78', $object->format(PhoneNumberFormat::NATIONAL));

        $object = new PhoneNumber('US+3212345678');
        self::assertTrue($object->isValid());
        self::assertEquals('BE', $object->getCountry());
        self::assertEquals('012 34 56 78', $object->format(PhoneNumberFormat::NATIONAL));
    }

    #[Test]
    public function it_formats_for_dialing_from_within_a_given_country(): void
    {
        $object = new PhoneNumber('+3212345678');
        self::assertEquals('012 34 56 78', $object->formatForCountry('BE'));
        self::assertEquals('00 32 12 34 56 78', $object->formatForCountry('NL'));
        self::assertEquals('011 32 12 34 56 78', $object->formatForCountry('US'));
    }

    #[Test]
    public function it_formats_for_dialing_on_mobile_from_within_a_given_country(): void
    {
        $object = new PhoneNumber('012 34 56 78', 'BE');
        self::assertEquals('012345678', $object->formatForMobileDialingInCountry('BE'));
        self::assertEquals('+3212345678', $object->formatForMobileDialingInCountry('NL'));
        self::assertEquals('+3212345678', $object->formatForMobileDialingInCountry('US'));
    }

    #[Test]
    public function it_throws_an_exception_when_an_invalid_country_is_provided_for_formatting_for_dialing(): void
    {
        $object = new PhoneNumber('+3212345678');

        $this->expectException(CountryCodeException::class);
        $this->expectExceptionMessage('foo');
        $object->formatForCountry('foo');
    }

    #[Test]
    public function it_throws_an_exception_when_an_invalid_country_is_provided_for_formatting_for_mobile_dialing(): void
    {
        $object = new PhoneNumber('+3212345678');

        $this->expectException(CountryCodeException::class);
        $this->expectExceptionMessage('foo');
        $object->formatForMobileDialingInCountry('foo');
    }

    #[Test]
    public function it_throws_an_exception_on_formatting_when_the_country_is_missing(): void
    {
        $object = new PhoneNumber('45678');

        $this->expectException(NumberParseException::class);
        $this->expectExceptionMessage('Number requires a country to be specified.');
        $object->formatRFC3966();
    }

    #[Test]
    public function it_throws_an_exception_on_formatting_when_the_country_is_mismatched(): void
    {
        $object = new PhoneNumber('45678', 'BE');

        $this->expectException(NumberParseException::class);
        $this->expectExceptionMessage('Number does not match the provided country');
        $object->formatRFC3966();
    }

    #[Test]
    public function it_handles_serialization(): void
    {
        $object = new PhoneNumber('+3212345678');
        $serialized = serialize($object);
        self::assertIsString($serialized);

        $unserialized = unserialize($serialized);
        self::assertInstanceOf(PhoneNumber::class, $unserialized);

        self::assertEquals('+3212345678', (string)$unserialized);
        self::assertEquals('BE', $unserialized->getCountry());
    }

    #[Test]
    public function it_casts_to_string(): void
    {
        $object = new PhoneNumber('012 34 56 78', 'BE');
        self::assertEquals($object->formatE164(), (string)$object);
    }

    #[Test]
    public function it_returns_the_original_number_when_unparsable_number_is_cast_to_string(): void
    {
        $object = new PhoneNumber('45678');
        self::assertEquals('45678', (string)$object);

        $object = new PhoneNumber('45678', 'BE');
        self::assertEquals('45678', (string)$object);
    }

    #[Test]
    public function it_returns_empty_string_when_null_is_cast_to_string(): void
    {
        $object = new PhoneNumber(null);
        self::assertEquals('', (string)$object);
    }

    #[Test]
    public function it_gets_the_exceptions_number(): void
    {
        $exception = NumberParseException::countryRequired('12345');
        self::assertEquals('12345', $exception->getNumber());

        $exception = NumberParseException::countryMismatch('12345', []);
        self::assertEquals('12345', $exception->getNumber());
    }

    #[Test]
    public function it_gets_the_exceptions_countries(): void
    {
        $exception = NumberParseException::countryMismatch('12345', ['BE', 'foo']);
        self::assertEquals(['BE', 'foo'], $exception->getCountries());
    }

    #[Test]
    public function it_can_check_equality(): void
    {
        $object = new PhoneNumber('012345678', ['AQ', 'BE']);

        self::assertTrue($object->equals('012345678', 'BE'));
        self::assertTrue($object->equals('012345678', ['BE', 'NL']));
        self::assertTrue($object->equals('+3212345678'));
        self::assertTrue($object->equals(new PhoneNumber('012345678', 'BE')));

        self::assertFalse($object->equals('012345679', 'BE'));
        self::assertFalse($object->equals('012345679', ['BE', 'NL']));
        self::assertFalse($object->equals('+3212345679'));
        self::assertFalse($object->equals(new PhoneNumber('012345679', 'BE')));
    }

    #[Test]
    public function it_can_check_inequality(): void
    {
        $object = new PhoneNumber('012345678', ['AQ', 'BE']);

        self::assertTrue($object->notEquals('012345679', 'BE'));
        self::assertTrue($object->notEquals('012345679', ['BE', 'NL']));
        self::assertTrue($object->notEquals('+3212345679'));
        self::assertTrue($object->notEquals(new PhoneNumber('012345679', 'BE')));

        self::assertFalse($object->notEquals('012345678', 'BE'));
        self::assertFalse($object->notEquals('012345678', ['BE', 'NL']));
        self::assertFalse($object->notEquals('+3212345678'));
        self::assertFalse($object->notEquals(new PhoneNumber('012345678', 'BE')));
    }

    #[Test]
    public function it_doesnt_throw_for_invalid_numbers_when_checking_equality(): void
    {
        $object = new PhoneNumber('012345678', ['AQ', 'BE']);

        self::assertFalse($object->equals('1234'));
        self::assertFalse($object->equals('012345678', 'NL'));
    }

    #[Test]
    public function it_doesnt_throw_for_invalid_numbers_when_checking_inequality(): void
    {
        $object = new PhoneNumber('012345678', ['AQ', 'BE']);

        self::assertTrue($object->notEquals('1234'));
        self::assertTrue($object->notEquals('012345678', 'NL'));
    }
}
