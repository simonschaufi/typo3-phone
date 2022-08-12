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

use SimonSchaufi\TYPO3Support\Collection;

class InvalidParameterException extends \Exception
{
    /**
     * Ambiguous parameter static constructor.
     */
    public static function ambiguous(string $parameter): InvalidParameterException
    {
        return new static('Ambiguous phone validation parameter: "' . $parameter
            . '". This parameter is recognized as an input field and as a phone type. Please rename the input field.');
    }

    /**
     * Invalid parameters static constructor.
     *
     * @param array|Collection $parameters
     * @return static
     */
    public static function parameters($parameters): InvalidParameterException
    {
        $parameters = Collection::make($parameters);

        return new static('Invalid phone validation parameters: "' . $parameters->implode(',') . '".');
    }
}
