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

namespace SimonSchaufi\TYPO3Phone\ViewHelpers;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Formats a phone number for display using Google's libphonenumber.
 *
 * Accepts raw numbers, tel: URIs (from TCA type "link" with allowedTypes telephone),
 * or any parseable phone number string.
 *
 * Examples:
 *   <phone:format value="tel:+3212345678" />                         → +32 12345678
 *   <phone:format value="tel:+3212345678" format="national" />       → 012 34 56 78
 *   <phone:format value="tel:+3212345678" format="e164" />           → +3212345678
 *   <phone:format value="tel:+3212345678" format="rfc3966" />        → tel:+32-12-34-56-78
 *   <phone:format value="+49(0)12-44 614038" />                      → +49 1244 614038
 */
final class FormatViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'Phone number or tel: link value');
        $this->registerArgument('defaultRegion', 'string', 'Default region for parsing numbers without country code');
        $this->registerArgument('format', 'string', 'The format to use (international, national, e164, rfc3966)', false, 'international');
    }

    public function render(): string
    {
        $value = $this->renderChildren();
        if ($value === null) {
            return '';
        }
        $value = trim((string)$value);

        if (str_starts_with($value, 'tel:')) {
            $value = substr($value, 4);
        }
        if ($value === '') {
            return '';
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        try {
            $number = $phoneUtil->parse($value, $this->arguments['defaultRegion']);
            if ($number === null) {
                return '';
            }
            $format = match ($this->arguments['format']) {
                'national' => PhoneNumberFormat::NATIONAL,
                'e164' => PhoneNumberFormat::E164,
                'rfc3966' => PhoneNumberFormat::RFC3966,
                default => PhoneNumberFormat::INTERNATIONAL,
            };
            return $phoneUtil->format($number, $format);
        } catch (NumberParseException) {
            // If parsing fails, return the raw value without tel: prefix
            return $value;
        }
    }

    /**
     * Explicitly set argument name to be used as content.
     */
    public function getContentArgumentName(): string
    {
        return 'value';
    }
}
