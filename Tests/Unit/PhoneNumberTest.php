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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use SimonSchaufi\TYPO3Phone\Exceptions\CountryCodeException;
use SimonSchaufi\TYPO3Phone\Exceptions\NumberFormatException;
use SimonSchaufi\TYPO3Phone\Exceptions\NumberParseException;
use SimonSchaufi\TYPO3Phone\PhoneNumber;

class PhoneNumberTest extends UnitTestCase
{
    /** @test */
    public function it_can_construct(): void
    {
        $object = new PhoneNumber('012345678');
        self::assertInstanceOf(PhoneNumber::class, $object);
        self::assertEquals('012345678', $object->getRawNumber());
    }

    /** @test */
    public function it_can_return_the_raw_number(): void
    {
        $object = new PhoneNumber('012 34 56 78');
        self::assertEquals('012 34 56 78', $object->getRawNumber());
    }

    /** @test */
    public function it_can_return_the_country(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('NL', 'FR', 'BE');
        self::assertEquals('BE', $object->getCountry());

        $object = new PhoneNumber('+3212345678');
        self::assertEquals('BE', $object->getCountry());
    }

    /** @test */
    public function it_stores_the_country(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('NL', 'FR', 'BE');
        self::assertEquals('BE', $object->getCountry());

        $object = new PhoneNumber('+3212345678');
        $object->getCountry();
        self::assertEquals('BE', $object->getCountry());
    }

    /** @test */
    public function it_can_check_the_country(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');
        self::assertTrue($object->isOfCountry('BE'));
        self::assertFalse($object->isOfCountry('US'));

        $object = new PhoneNumber('+3212345678');
        self::assertTrue($object->isOfCountry('BE'));
        self::assertFalse($object->isOfCountry('US'));
    }

    /** @test */
    public function it_can_make(): void
    {
        $object = PhoneNumber::make('012345678');
        self::assertInstanceOf(PhoneNumber::class, $object);
        self::assertEquals('012345678', (string)$object);

        $object = PhoneNumber::make('012345678', 'BE');
        self::assertEquals('+3212345678', (string)$object);
        self::assertEquals('BE', $object->getCountry());

        $object = PhoneNumber::make('012345678', ['BE', 'NL']);
        self::assertEquals('+3212345678', (string)$object);
        self::assertEquals('BE', $object->getCountry());
    }

    /** @test */
    public function it_can_format(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');
        self::assertEquals('+3212345678', $object->format(PhoneNumberFormat::E164));
    }

    /** @test */
    public function it_can_format_international_numbers_without_given_country(): void
    {
        $object = new PhoneNumber('+3212345678');
        self::assertEquals('012 34 56 78', $object->format(PhoneNumberFormat::NATIONAL));
    }

    /** @test */
    public function it_throws_an_exception_when_formatting_non_international_number_without_given_country(): void
    {
        $object = new PhoneNumber('012345678');

        $this->expectException(NumberParseException::class);
        $this->expectExceptionMessage('Number requires a country to be specified.');
        $object->format(PhoneNumberFormat::NATIONAL);
    }

    /** @test */
    public function it_can_parse_format_names(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');
        self::assertEquals(
            $object->format(PhoneNumberFormat::E164),
            $object->format('e164')
        );
    }

    /** @test */
    public function it_throws_an_exception_for_invalid_formats(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');

        $this->expectException(NumberFormatException::class);
        $this->expectExceptionMessage('foo');
        $object->format('foo');
    }

    /** @test */
    public function it_has_an_international_format_shortcut_method(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');
        self::assertEquals(
            $object->format(PhoneNumberFormat::INTERNATIONAL),
            $object->formatInternational()
        );
    }

    /** @test */
    public function it_has_a_national_format_shortcut_method(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');
        self::assertEquals(
            $object->format(PhoneNumberFormat::NATIONAL),
            $object->formatNational()
        );
    }

    /** @test */
    public function it_has_an_E164_format_shortcut_method(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');
        self::assertEquals(
            $object->format(PhoneNumberFormat::E164),
            $object->formatE164()
        );
    }

    /** @test */
    public function it_has_an_RFC3966_format_shortcut_method(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');
        self::assertEquals(
            $object->format(PhoneNumberFormat::RFC3966),
            $object->formatRFC3966()
        );
    }

    /** @test */
    public function it_can_format_for_dialing_from_within_a_given_country(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');
        self::assertEquals('012 34 56 78', $object->formatForCountry('BE'));
        self::assertEquals('00 32 12 34 56 78', $object->formatForCountry('NL'));
        self::assertEquals('011 32 12 34 56 78', $object->formatForCountry('US'));
    }

    /** @test */
    public function it_can_format_for_dialing_on_mobile_from_within_a_given_country(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');
        self::assertEquals('012345678', $object->formatForMobileDialingInCountry('BE'));
        self::assertEquals('+3212345678', $object->formatForMobileDialingInCountry('NL'));
        self::assertEquals('+3212345678', $object->formatForMobileDialingInCountry('US'));
    }

    /** @test */
    public function it_throws_an_exception_when_an_invalid_country_is_provided_for_formatting_for_dialing(): void
    {
        $object = new PhoneNumber('+3212345678');

        $this->expectException(CountryCodeException::class);
        $this->expectExceptionMessage('foo');
        $object->formatForCountry('foo');
    }

    /** @test */
    public function it_throws_an_exception_when_an_invalid_country_is_provided_for_formatting_for_mobile_dialing(): void
    {
        $object = new PhoneNumber('+3212345678');
        $this->expectException(CountryCodeException::class);
        $this->expectExceptionMessage('foo');
        $object->formatForMobileDialingInCountry('foo');
    }

    /** @test */
    public function it_can_verify_formats(): void
    {
        self::assertTrue(PhoneNumber::isValidFormat(PhoneNumberFormat::E164));
        self::assertTrue(PhoneNumber::isValidFormat('e164'));
        self::assertFalse(PhoneNumber::isValidFormat('99999'));
        self::assertFalse(PhoneNumber::isValidFormat('foo'));
    }

    /** @test */
    public function it_throws_an_exception_when_the_country_is_mismatched(): void
    {
        $object = new PhoneNumber('4567');
        $object = $object->ofCountry('BE');

        $this->expectException(NumberParseException::class);
        $this->expectExceptionMessage('Number requires a country to be specified.');
        $object->formatRFC3966();
    }

    /** @test */
    public function it_can_return_the_type(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');
        self::assertEquals('fixed_line', $object->getType());
        self::assertEquals(PhoneNumberType::FIXED_LINE, $object->getType(true));

        $object = new PhoneNumber('0470123456');
        $object = $object->ofCountry('BE');
        self::assertEquals('mobile', $object->getType());
        self::assertEquals(PhoneNumberType::MOBILE, $object->getType(true));
    }

    /** @test */
    public function it_can_check_the_type(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');
        self::assertTrue($object->isOfType('fixed_line'));
        self::assertTrue($object->isOfType(PhoneNumberType::FIXED_LINE));
        self::assertFalse($object->isOfType('mobile'));
        self::assertFalse($object->isOfType(PhoneNumberType::MOBILE));

        $object = new PhoneNumber('0470123456');
        $object = $object->ofCountry('BE');
        self::assertFalse($object->isOfType('fixed_line'));
        self::assertFalse($object->isOfType(PhoneNumberType::FIXED_LINE));
        self::assertTrue($object->isOfType('mobile'));
        self::assertTrue($object->isOfType(PhoneNumberType::MOBILE));
    }

    /** @test */
    public function it_adds_the_unsure_type(): void
    {
        // This number is of type FIXED_LINE_OR_MOBILE.
        // Without the unsure type, the following check would fail.
        $object = new PhoneNumber('8590332334');
        $object = $object->ofCountry('IN');
        self::assertTrue($object->isOfType('fixed_line'));
    }

    /** @test */
    public function it_can_verify_types(): void
    {
        self::assertTrue(PhoneNumber::isValidType(PhoneNumberType::MOBILE));
        self::assertFalse(PhoneNumber::isValidType((string)PhoneNumberType::MOBILE)); // fails due to strict mode
        self::assertTrue(PhoneNumber::isValidType('mobile'));
        // self::assertFalse(PhoneNumber::isValidType(99999)); // fails due to strict mode
        self::assertFalse(PhoneNumber::isValidType('99999'));
        self::assertFalse(PhoneNumber::isValidType('foo'));
    }

    /** @test */
    public function it_can_handle_json_encoding(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');

        self::assertEquals('"+3212345678"', $object->toJson());
        self::assertEquals('"+3212345678"', json_encode($object));
    }

    /** @test */
    public function it_can_handle_serialization(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');
        $serialized = serialize($object);
        self::assertIsString($serialized);

        /* @var PhoneNumber $unserialized */
        $unserialized = unserialize($serialized);
        self::assertInstanceOf(PhoneNumber::class, $unserialized);
        self::assertEquals('+3212345678', $unserialized->getRawNumber());
        self::assertEquals('BE', $unserialized->getCountry());
    }

    /** @test */
    public function it_can_be_cast_to_string(): void
    {
        $object = new PhoneNumber('012345678');
        $object = $object->ofCountry('BE');
        self::assertEquals($object->formatE164(), (string)$object);
    }

    /** @test */
    public function it_returns_the_original_number_when_unparsable_number_is_cast_to_string(): void
    {
        $object = new PhoneNumber('45678');
        self::assertEquals('45678', (string)$object);

        $object = $object->ofCountry('BE');
        self::assertEquals('45678', (string)$object);
    }
}
