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

namespace GertKaaeHansen\TYPO3Phone\Tests\Unit\Validation\Validator;

use Nimut\TestingFramework\MockObject\AccessibleMockObjectInterface;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use SimonSchaufi\TYPO3Phone\Exceptions\InvalidParameterException;
use SimonSchaufi\TYPO3Phone\Validation\Validator\PhoneValidator;
use TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException;

class PhoneValidatorTest extends UnitTestCase
{
    /**
     * @var string
     */
    protected $validatorClassName = PhoneValidator::class;

    /**
     * @param array $options
     * @param array $mockedMethods
     * @return AccessibleMockObjectInterface|MockObject|PhoneValidator
     */
    protected function getValidator(array $options = [], array $mockedMethods = ['translateErrorMessage'])
    {
        return $this->getAccessibleMock($this->validatorClassName, $mockedMethods, [$options]);
    }

    /** @test */
    public function it_validates_with_default_countries_without_type(): void
    {
        // Validator with correct country field.
        self::assertFalse($this->getValidator(['countries' => ['BE']])->validate('012345678')->hasErrors());

        // Validator with wrong country value.
        self::assertTrue($this->getValidator(['countries' => ['NL']])->validate('012345678')->hasErrors());

        // Validator with multiple country values, one correct.
        self::assertFalse($this->getValidator(['countries' => ['BE', 'NL']])->validate('012345678')->hasErrors());

        // Validator with multiple country values, value correct for second country in list.
        self::assertFalse($this->getValidator(['countries' => ['NL', 'BE']])->validate('012345678')->hasErrors());

        // Validator with multiple wrong country values.
        self::assertTrue($this->getValidator(['countries' => ['DE', 'NL']])->validate('012345678')->hasErrors());
    }

    /** @test */
    public function it_validates_with_default_countries_with_type(): void
    {
        // Validator with correct country value, correct type.
        self::assertFalse($this->getValidator(['countries' => ['BE'], 'types' => ['mobile']])->validate('0470123456')->hasErrors());

        // Validator with correct country value, wrong type.
        self::assertTrue($this->getValidator(['countries' => ['BE'], 'types' => ['mobile']])->validate('012345678')->hasErrors());

        // Validator with wrong country value, correct type.
        self::assertTrue($this->getValidator(['countries' => ['NL'], 'types' => ['mobile']])->validate('0470123456')->hasErrors());

        // Validator with wrong country value, wrong type.
        self::assertTrue($this->getValidator(['countries' => ['NL'], 'types' => ['mobile']])->validate('012345678')->hasErrors());

        // Validator with multiple country values, one correct, correct type.
        self::assertFalse($this->getValidator(['countries' => ['BE', 'NL'], 'types' => ['mobile']])->validate('0470123456')->hasErrors());

        // Validator with multiple country values, one correct, wrong type.
        self::assertTrue($this->getValidator(['countries' => ['BE', 'NL'], 'types' => ['mobile']])->validate('012345678')->hasErrors());

        // Validator with multiple country values, none correct, correct type.
        self::assertTrue($this->getValidator(['countries' => ['DE', 'NL'], 'types' => ['mobile']])->validate('0470123456')->hasErrors());

        // Validator with multiple country values, none correct, wrong type.
        self::assertTrue($this->getValidator(['countries' => ['DE', 'NL'], 'types' => ['mobile']])->validate('012345678')->hasErrors());
    }

    /** @test */
    public function it_validates_with_automatic_detection(): void
    {
        // Validator with correct international input.
        self::assertFalse($this->getValidator(['countries' => ['AUTO']])->validate('+3212345678')->hasErrors());

        // Validator with wrong international input.
        self::assertTrue($this->getValidator(['countries' => ['AUTO']])->validate('003212345678')->hasErrors());

        // Validator with wrong international input.
        self::assertTrue($this->getValidator(['countries' => ['AUTO']])->validate('+321234')->hasErrors());

        // Validator with wrong international input but correct default country.
        self::assertFalse($this->getValidator(['countries' => ['AUTO', 'NL', 'BE']])->validate('012345678')->hasErrors());

        // Validator with wrong international input and wrong default country.
        self::assertTrue($this->getValidator(['countries' => ['AUTO', 'DE', 'NL']])->validate('012345678')->hasErrors());
    }

    /** @test */
    public function it_validates_without_countries(): void
    {
        // Validator with no country field or given country.
        self::assertTrue($this->getValidator(['countries' => []])->validate('012345678')->hasErrors());

        $this->expectException(InvalidParameterException::class);

        // Validator with no country field or given country, wrong type.
        self::assertTrue($this->getValidator(['countries' => ['mobile']])->validate('012345678')->hasErrors());

        // Validator with no country field or given country, correct type.
        self::assertTrue($this->getValidator(['countries' => ['mobile']])->validate('0470123456')->hasErrors());
    }

    /** @test */
    public function it_throws_an_exception_for_invalid_options_parameters(): void
    {
        $this->expectException(InvalidValidationOptionsException::class);

        $this->getValidator(['country' => ['BE']])->validate('0470123456');
    }

    /** @test */
    public function it_validates_lenient(): void
    {
        // Validator with AU area code, lenient off
        self::assertTrue($this->getValidator(['countries' => ['AU']])->validate('12345678')->hasErrors());

        // Validator with AU area code, lenient on
        self::assertFalse($this->getValidator(['countries' => ['LENIENT', 'AU']])->validate('12345678')->hasErrors());

        // Validator with correct country field supplied, lenient on
        self::assertFalse($this->getValidator(['countries' => ['LENIENT', 'AU']])->validate('12345678')->hasErrors());

        // Validator with wrong country field supplied, lenient on
        self::assertFalse($this->getValidator(['countries' => ['LENIENT', 'BE']])->validate('12345678')->hasErrors());

        // Validator with no area code, lenient on
        self::assertFalse($this->getValidator(['countries' => ['LENIENT']])->validate('+12015550123')->hasErrors());

        // Validator with US area code, lenient on
        self::assertFalse($this->getValidator(['countries' => ['LENIENT', 'US']])->validate('+16502530000')->hasErrors());

        // Validator with no area code, lenient off
        self::assertTrue($this->getValidator(['countries' => ['LENIENT']])->validate('2015550123')->hasErrors());

        // Validator with US area code, lenient on
        self::assertFalse($this->getValidator(['countries' => ['LENIENT', 'US']])->validate('2015550123')->hasErrors());

        // Validator with US area code, lenient off
        self::assertTrue($this->getValidator(['countries' => ['LENIENT']])->validate('5550123')->hasErrors());

        // Validator with US area code, lenient on
        self::assertFalse($this->getValidator(['countries' => ['LENIENT', 'US']])->validate('5550123')->hasErrors());
    }
}
