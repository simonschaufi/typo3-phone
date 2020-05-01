# TYPO3 Phone

[![Latest Stable Version](https://poser.pugx.org/simonschaufi/typo3-phone/v/stable)](https://packagist.org/packages/simonschaufi/typo3-phone)
[![Total Downloads](https://poser.pugx.org/simonschaufi/typo3-phone/downloads)](https://packagist.org/packages/simonschaufi/typo3-phone)
[![License](https://poser.pugx.org/simonschaufi/typo3-phone/license)](https://packagist.org/packages/simonschaufi/typo3-phone)
[![TYPO3](https://img.shields.io/badge/TYPO3-8.7.0-orange.svg)](https://typo3.org/)
[![TYPO3](https://img.shields.io/badge/TYPO3-9.5.0-orange.svg)](https://typo3.org/)

Adds phone number functionality to TYPO3 based on the [PHP port](https://github.com/giggsey/libphonenumber-for-php) of [Google's libphonenumber API](https://github.com/googlei18n/libphonenumber) by [giggsey](https://github.com/giggsey).

## Installation

```bash
composer require simonschaufi/typo3-phone
```

## Usage

For each action (here updateAction) you want to validate your object (in our case an Address with two properties "phone" and "fax")
add the following code in your controller:

```php
use SimonSchaufi\TYPO3Phone\Validation\Validator\PhoneValidator;

public function initializeUpdateAction(): void
{
	if ($this->request->hasArgument('address') && $this->request->getArgument('address')) {
		/** @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver */
		$addressValidator = $this->validatorResolver->getBaseValidatorConjunction(Address::class);
    
        $validators = $addressValidator->getValidators();
        $validators->rewind();
        $validator = $validators->current();

        /* @var \TYPO3\CMS\Extbase\Validation\Validator\GenericObjectValidator $validator */
        $phoneValidator = $this->validatorResolver->createValidator(PhoneValidator::class, [
            // If the user enters a number prefixed with "+" then the country can be guessed.
            // If not, the following countries listed in the array will be checked against
            'countries' => ['AUTO', 'DE']
        ]);
        $validator->addPropertyValidator('phone', $phoneValidator);
        $validator->addPropertyValidator('fax', $phoneValidator);
	}
}
```

You instruct the validator to detect which country the number belongs to using the `AUTO` keyword (and optionally any fallback countries).

For any fallbacks, use the [*ISO 3166-1 alpha-2 compliant*](http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements) country codes, feel free to add as many country codes as you like.

The validator will try to extract the country from the number itself and then check if the number is valid for that country. If the country could not be guessed it will be validated using the fallback countries if provided. Note that country guessing will only work when phone numbers are entered in *international format* (prefixed with a `+` sign, e.g. +32 ....). Leading double zeros will **NOT** be parsed correctly as this isn't an established consistency.

if you want to use the validator within a controller action, use the following code:

Info: In my case the Address Object has a property "country" that is of type `\SJBR\StaticInfoTables\Domain\Model\Country`

```php
use SimonSchaufi\TYPO3Phone\Exception\NumberParseException;
use SimonSchaufi\TYPO3Phone\PhoneNumber;

if (strlen($address->getPhone()) > 0) {
	try {
		$phoneNumber = PhoneNumber::make($address->getPhone(), [$address->getCountry()->getIsoCodeA2()])->formatInternational();
		$address->setPhone($phoneNumber);
	} catch (NumberParseException $exception) {
		$this->errorAction();
	}
}
```

## Utility PhoneNumber class

A phone number can be wrapped in the `SimonSchaufi\TYPO3Phone\PhoneNumber` class to enhance it with useful utility 
methods. It's safe to directly reference these objects in views or when saving to the database as they will degrade 
gracefully to the E164 format.

```php
use SimonSchaufi\TYPO3Phone\PhoneNumber;

(string) PhoneNumber::make('+3212/34.56.78');              // +3212345678
(string) PhoneNumber::make('012 34 56 78', 'BE');          // +3212345678
(string) PhoneNumber::make('012345678')->ofCountry('BE');  // +3212345678
```

### Formatting
A PhoneNumber can be formatted in various ways:

```php
use SimonSchaufi\TYPO3Phone\PhoneNumber;

PhoneNumber::make('012 34 56 78', 'BE')->format($format);       // See libphonenumber\PhoneNumberFormat
PhoneNumber::make('012 34 56 78', 'BE')->formatE164();          // +3212345678
PhoneNumber::make('012 34 56 78', 'BE')->formatInternational(); // +32 12 34 56 78
PhoneNumber::make('012 34 56 78', 'BE')->formatRFC3966();       // +32-12-34-56-78
PhoneNumber::make('012/34.56.78', 'BE')->formatNational();      // 012 34 56 78

// Formats so the number can be called straight from the provided country.
PhoneNumber::make('012 34 56 78', 'BE')->formatForCountry('BE'); // 012 34 56 78
PhoneNumber::make('012 34 56 78', 'BE')->formatForCountry('NL'); // 00 32 12 34 56 78
PhoneNumber::make('012 34 56 78', 'BE')->formatForCountry('US'); // 011 32 12 34 56 78

// Formats so the number can be clicked on and called straight from the provided country using a cellphone.
PhoneNumber::make('012 34 56 78', 'BE')->formatForMobileDialingInCountry('BE'); // 012345678
PhoneNumber::make('012 34 56 78', 'BE')->formatForMobileDialingInCountry('NL'); // +3212345678
PhoneNumber::make('012 34 56 78', 'BE')->formatForMobileDialingInCountry('US'); // +3212345678
```

### Number information
Get some information about the phone number:

```php
use SimonSchaufi\TYPO3Phone\PhoneNumber;

PhoneNumber::make('012 34 56 78', 'BE')->getType();              // 'fixed_line'
PhoneNumber::make('012 34 56 78', 'BE')->isOfType('fixed_line'); // true
PhoneNumber::make('012 34 56 78', 'BE')->getCountry();           // 'BE'
PhoneNumber::make('012 34 56 78', 'BE')->isOfCountry('BE');      // true
PhoneNumber::make('+32 12 34 56 78')->isOfCountry('BE');         // true
```

## Giving thanks

This extension is heavily inspired by https://github.com/Propaganistas/Laravel-Phone. Thank you 
