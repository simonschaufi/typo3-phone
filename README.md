# TYPO3 Phone

[![Donate](https://img.shields.io/badge/Donate-PayPal-blue.svg)](https://www.paypal.me/simonschaufi/20)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/simonschaufi?label=GitHub%20Sponsors)](https://github.com/sponsors/simonschaufi)
[![Buy me a coffee](https://img.shields.io/badge/-Buy_me_a_coffee-gray?logo=buymeacoffee)](https://www.buymeacoffee.com/simonschaufi)
[![CI](https://github.com/simonschaufi/typo3-phone/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/simonschaufi/typo3-phone/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/simonschaufi/typo3-phone/v/stable)](https://packagist.org/packages/simonschaufi/typo3-phone)
[![Total Downloads](https://poser.pugx.org/simonschaufi/typo3-phone/downloads)](https://packagist.org/packages/simonschaufi/typo3-phone)
[![License](https://poser.pugx.org/simonschaufi/typo3-phone/license)](https://packagist.org/packages/simonschaufi/typo3-phone)
[![TYPO3](https://img.shields.io/badge/TYPO3-13-orange.svg)](https://get.typo3.org/version/13)

Adds phone number functionality to TYPO3 based on the [PHP port](https://github.com/giggsey/libphonenumber-for-php)
of [libphonenumber by Google](https://github.com/googlei18n/libphonenumber).

## Installation

Run the following command to install the latest applicable version of the package:

```bash
composer require simonschaufi/typo3-phone
```

## Validation

For each action (here `updateAction`) you want to validate your object (in this case an Address with two properties "phone" and "fax")
add the following code in your controller:

```php
use SimonSchaufi\TYPO3Phone\Validation\Validator\PhoneValidator;

public function initializeUpdateAction(): void
{
	if ($this->request->hasArgument('address') && $this->request->getArgument('address')) {
		$addressValidator = $this->validatorResolver->getBaseValidatorConjunction(Address::class);

		$validators = $addressValidator->getValidators();
		$validators->rewind();
		$validator = $validators->current();

		/** @var PhoneValidator $phoneValidator */
		$phoneValidator = $this->validatorResolver->createValidator(PhoneValidator::class, [
			// If the user enters a number prefixed with "+" then the country can be guessed.
			// If not, the following countries listed in the array will be checked against
			'countries' => ['DE'],
			'international' => true,
		]);

		$validator->addPropertyValidator('phone', $phoneValidator);
		$validator->addPropertyValidator('fax', $phoneValidator);
	}
}
```

Alternatively you can instantiate the validator anywhere in your code like this:

```php
use SimonSchaufi\TYPO3Phone\Validation\Validator\PhoneValidator;

$phoneValidator = GeneralUtility::makeInstance(PhoneValidator::class);
$phoneValidator->setOptions([
	// If the user enters a number prefixed with "+" then the country can be guessed.
	// If not, the following countries listed in the array will be checked against
	'countries' => ['DE'],
	'types' => ['mobile'],
	'international' => true,
]);

$result = $phoneValidator->validate('+3212345678');

if ($result->hasErrors()) {
	// Error handling
}
```

Note: country codes should be [*ISO 3166-1 alpha-2 compliant*](http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements).

To support _any valid internationally formatted_ phone number next to the whitelisted countries, use the `international` option.
This can be useful when you're expecting locally formatted numbers from a specific country but also want to accept any other foreign number entered properly:

```php
$phoneValidator->setOptions([
    'international' => true,
]);
```

The validator will try to extract the country from the number itself and then check if the number is valid for that country.
If the country could not be guessed it will be validated using the fallback countries if provided.
Note that country guessing will only work when phone numbers are entered in *international format* (prefixed with a `+` sign, e.g. +32 ....).
Leading double zeros will **NOT** be parsed correctly as this isn't an established consistency.

To specify constraints on the number type, set the allowed types, e.g.:

```php
$phoneValidator->setOptions([
    'types' => ['mobile'],
]);
```
The most common types are `mobile` and `fixed_line`, but feel free to use any of the types defined [here](https://github.com/giggsey/libphonenumber-for-php/blob/master/src/PhoneNumberType.php).

You can also enable lenient validation by using the `lenient` option.
With leniency enabled, only the length of the number is checked instead of actual carrier patterns.

```php
$phoneValidator->setOptions([
    'lenient' => true,
]);
```

### Validation outside of Extbase

If you **don't** want to use the extbase validator and instead a more low level approach, use the following code:

Info: In this case the Address object has a property "country" that is of type `\SJBR\StaticInfoTables\Domain\Model\Country`

```php
use SimonSchaufi\TYPO3Phone\Exceptions\NumberParseException;
use SimonSchaufi\TYPO3Phone\PhoneNumber;

if (!empty($address->getPhone())) {
	try {
		$phoneNumber = (new PhoneNumber($address->getPhone(), [$address->getCountry()->getIsoCodeA2()]))->formatInternational();
		$address->setPhone($phoneNumber);
	} catch (NumberParseException $exception) {
		// Error handling
	}
}
```

## Utility PhoneNumber class

A phone number can be wrapped in the `SimonSchaufi\TYPO3Phone\PhoneNumber` class to enhance it with useful utility
methods. It's safe to directly reference these objects in views or when saving to the database as they will degrade
gracefully to the E.164 format.

```php
use SimonSchaufi\TYPO3Phone\PhoneNumber;

(string) new PhoneNumber('+3212/34.56.78');     // +3212345678
(string) new PhoneNumber('012 34 56 78', 'BE'); // +3212345678
```

### Formatting

A PhoneNumber can be formatted in various ways:

```php
use SimonSchaufi\TYPO3Phone\PhoneNumber;

$phone = new PhoneNumber('012/34.56.78', 'BE');

$phone->format($format);       // See libphonenumber\PhoneNumberFormat
$phone->formatE164();          // +3212345678
$phone->formatInternational(); // +32 12 34 56 78
$phone->formatRFC3966();       // +32-12-34-56-78
$phone->formatNational();      // 012 34 56 78

// Formats so the number can be called straight from the provided country.
$phone->formatForCountry('BE'); // 012 34 56 78
$phone->formatForCountry('NL'); // 00 32 12 34 56 78
$phone->formatForCountry('US'); // 011 32 12 34 56 78

// Formats so the number can be clicked on and called straight from the provided country using a cellphone.
$phone->formatForMobileDialingInCountry('BE'); // 012345678
$phone->formatForMobileDialingInCountry('NL'); // +3212345678
$phone->formatForMobileDialingInCountry('US'); // +3212345678
```

### Number information

Get some information about the phone number:

```php
use SimonSchaufi\TYPO3Phone\PhoneNumber;

$phone = new PhoneNumber('012 34 56 78', 'BE');

$phone->getType();              // 'fixed_line'
$phone->isOfType('fixed_line'); // true
$phone->getCountry();           // 'BE'
$phone->isOfCountry('BE');      // true
```

### Equality comparison

Check if a given phone number is (not) equal to another one:

```php
use SimonSchaufi\TYPO3Phone\PhoneNumber;

$phone = new PhoneNumber('012 34 56 78', 'BE');

$phone->equals('012/34.56.78', 'BE')       // true
$phone->equals('+32 12 34 56 78')          // true
$phone->equals($anotherPhoneObject)        // true/false

$phone->notEquals('045 67 89 10', 'BE')    // true
$phone->notEquals('+32 45 67 89 10')       // true
$phone->notEquals($anotherPhoneObject)     // true/false
```

## Database considerations

> Disclaimer: Phone number handling is quite different in each application. The topics mentioned below are therefore meant as a set of thought starters; support will **not** be provided.

Storing phone numbers in a database has always been a speculative topic and there's simply no silver bullet.
It all depends on your application's requirements. Here are some things to take into account, along with an implementation suggestion.
Your ideal database setup will probably be a combination of some of the pointers detailed below.

### Uniqueness

The E.164 format globally and uniquely identifies a phone number across the world.
It also inherently implies a specific country and can be supplied as-is to the `phone()` helper.

You'll need:

* One column to store the phone number
* To format the phone number to E.164 before persisting it

Example:

* User input = `012/45.65.78`
* Database column
  * `phone` (varchar) = `+3212456578`

### Presenting the phone number the way it was inputted

If you store formatted phone numbers the raw user input will irretrievably get lost.
It may be beneficial to present your users with their very own inputted phone number,
for example in terms of improved user experience.

You'll need:
* Two columns to store the raw input and the correlated country

Example:

* User input = `012/34.56.78`
* Database columns
  * `phone` (varchar) = `012/34.56.78`
  * `phone_country` (varchar) = `BE`

### Supporting searches

Searching through phone numbers can quickly become ridiculously complex and will always require deep understanding of
the context and extent of your application. Here's _a_ possible approach covering quite a lot of "natural" use cases.

You'll need:
* Three additional columns to store searchable variants of the phone number:
  * Normalized input (raw input with all non-alpha characters stripped)
  * National formatted phone number (with all non-alpha characters stripped)
  * E.164 formatted phone number
* An extensive search query utilizing the searchable variants

Example:

* User input = `12/34.56.78`
* Database columns
  * `phone_normalized` (varchar) = `12345678`
  * `phone_national` (varchar) = `012345678`
  * `phone_e164` (varchar) = `+3212345678`

## Need help with integrating this extension into your website?

Please contact me via my website: https://www.simonschaufelberger.de/de/kontakt.html and I will help you!

## Giving thanks

This extension is heavily inspired by https://github.com/Propaganistas/Laravel-Phone. Thank you!
