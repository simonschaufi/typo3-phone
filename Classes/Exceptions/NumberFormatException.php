<?php
declare(strict_types=1);

namespace SimonSchaufi\TYPO3Phone\Exceptions;

class NumberFormatException extends \Exception
{
    /**
     * Invalid number format static constructor.
     *
     * @param string $format
     * @return static
     */
    public static function invalid($format)
    {
        return new static('Invalid number format "' . $format . '".');
    }
}
