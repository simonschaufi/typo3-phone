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

namespace SimonSchaufi\TYPO3Phone\Tests\Functional\Validation\Validator;

use libphonenumber\PhoneNumberType;
use SimonSchaufi\TYPO3Phone\Validation\Validator\PhoneValidator;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PhoneValidatorTest extends FunctionalTestCase
{
    /**
     * @see https://github.com/TYPO3/typo3/blob/fc5e9f7e37dd15d873d949c84cb603e0968ea202/typo3/sysext/extbase/Tests/Functional/Validation/Validator/BooleanValidatorTest.php#L28-L34
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $GLOBALS['TYPO3_REQUEST'] = $request;
    }

    private function getValidator(array $options = []): PhoneValidator
    {
        $validator = new PhoneValidator();
        $validator->setOptions($options);
        return $validator;
    }

    private function validate(string $value, array $options = []): Result
    {
        return $this->getValidator($options)->validate($value);
    }

    /** @test */
    public function it_validates_with_explicit_countries(): void
    {
        self::assertFalse($this->validate('012345678', ['countries' => ['BE']])->hasErrors());
        self::assertFalse($this->validate('012345678', ['countries' => ['NL', 'BE', 'US']])->hasErrors());
        self::assertTrue($this->validate('012345678', ['countries' => ['NL']])->hasErrors());
        self::assertTrue($this->validate('012345678', ['countries' => ['DE', 'NL', 'US']])->hasErrors());
    }

    /** @test */
    public function it_validates_without_countries(): void
    {
        self::assertFalse($this->validate('+3212345678')->hasErrors());
        self::assertTrue($this->validate('003212345678')->hasErrors());
        self::assertTrue($this->validate('+321234')->hasErrors());
    }

    /** @test */
    public function it_validates_in_international_mode(): void
    {
        self::assertTrue($this->validate('+3212345678', ['countries' => ['NL']])->hasErrors());
        self::assertFalse($this->validate('+3212345678', ['countries' => ['NL'], 'international' => true])->hasErrors());
        self::assertTrue($this->validate('012345678', ['countries' => ['NL'], 'international' => true])->hasErrors());
        self::assertFalse($this->validate('012345678', ['countries' => ['BE'], 'international' => true])->hasErrors());
    }

    /** @test */
    public function it_throws_an_exception_for_invalid_options_parameters(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);

        $this->getValidator(['country' => ['BE']])->validate('0470123456');
    }

    /** @test */
    public function it_validates_in_lenient_mode(): void
    {
        // Validator with AU area code, lenient off
        self::assertTrue($this->validate('12345678', ['countries' => ['AU']])->hasErrors());

        // Validator with AU area code, lenient on
        self::assertFalse($this->validate('12345678', ['countries' => ['AU'], 'lenient' => true])->hasErrors());

        self::assertTrue($this->validate('+49(0)12-44 614038', ['countries' => [], 'lenient' => false])->hasErrors());

        self::assertFalse($this->validate('+49(0)12-44 614038', ['countries' => [], 'lenient' => true])->hasErrors());

        // Validator with correct country field supplied, lenient on
        self::assertFalse($this->validate('12345678', ['countries' => ['AU'], 'lenient' => true])->hasErrors());

        // Validator with wrong country field supplied, lenient on
        self::assertFalse($this->validate('12345678', ['countries' => ['BE'], 'lenient' => true])->hasErrors());

        // Validator with no area code, lenient on
        self::assertFalse($this->validate('+12015550123', ['countries' => [], 'lenient' => true])->hasErrors());

        // Validator with US area code, lenient on
        self::assertFalse($this->validate('+16502530000', ['countries' => ['US'], 'lenient' => true])->hasErrors());

        // Validator with no area code, lenient off
        self::assertTrue($this->validate('2015550123', ['countries' => [], 'lenient' => true])->hasErrors());

        // Validator with US area code, lenient on
        self::assertFalse($this->validate('2015550123', ['countries' => ['US'], 'lenient' => true])->hasErrors());

        // Validator with US area code, lenient off
        self::assertTrue($this->validate('5550123', ['countries' => [], 'lenient' => true])->hasErrors());

        // Validator with US area code, lenient on
        self::assertFalse($this->validate('5550123', ['countries' => ['US'], 'lenient' => true])->hasErrors());
    }

    /** @test */
    public function it_validates_type(): void
    {
        self::assertFalse($this->validate('+32470123456', ['types' => [PhoneNumberType::MOBILE]])->hasErrors());
        self::assertTrue($this->validate('+3212345678', ['types' => [PhoneNumberType::MOBILE]])->hasErrors());
    }

    /** @test */
    public function it_validates_type_and_explicit_country_combined(): void
    {
        self::assertFalse($this->validate('0470123456', ['countries' => ['BE'], 'types' => [PhoneNumberType::MOBILE]])->hasErrors());
        self::assertTrue($this->validate('012345678', ['countries' => ['BE'], 'types' => [PhoneNumberType::MOBILE]])->hasErrors());
        self::assertTrue($this->validate('0470123456', ['countries' => ['NL'], 'types' => [PhoneNumberType::MOBILE]])->hasErrors());
    }

    /** @test */
    public function it_validates_type_as_string(): void
    {
        self::assertFalse($this->validate('+32470123456', ['types' => ['mobile']])->hasErrors());
        self::assertTrue($this->validate('+3212345678', ['types' => ['mobile']])->hasErrors());
    }

    /** @test */
    public function it_accepts_mixed_case_parameters(): void
    {
        self::assertFalse($this->validate('+32470123456', ['types' => ['mObIlE']])->hasErrors());
        self::assertFalse($this->validate('0470123456', ['countries' => ['bE']])->hasErrors());
    }

    /** @test */
    public function it_validates_explicit_lowercase_countries(): void
    {
        self::assertFalse($this->validate('0470123456', ['countries' => ['be']])->hasErrors());
        self::assertTrue($this->validate('0470123456', ['countries' => ['us']])->hasErrors());
    }

    /** @test */
    public function it_validates_libphonenumber_specific_regions_as_country(): void
    {
        self::assertFalse($this->validate('+247501234', ['countries' => ['AC']])->hasErrors());
    }
}
