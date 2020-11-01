<?php
declare(strict_types=1);

namespace SimonSchaufi\TYPO3Phone\Exceptions;

use SimonSchaufi\TYPO3Support\Collection;

class InvalidParameterException extends \Exception
{
    /**
     * Ambiguous parameter static constructor.
     *
     * @param string $parameter
     * @return static
     */
    public static function ambiguous(string $parameter)
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
    public static function parameters($parameters)
    {
        $parameters = Collection::make($parameters);

        return new static('Invalid phone validation parameters: "' . $parameters->implode(',') . '".');
    }
}
