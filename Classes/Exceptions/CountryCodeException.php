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

namespace SimonSchaufi\TYPO3Phone\Exceptions;

class CountryCodeException extends \Exception
{
    /**
     * Invalid country code static constructor.
     *
     * @param string $country
     * @return static
     */
    public static function invalid(string $country)
    {
        return new static('Invalid country code "' . $country . '".');
    }
}
