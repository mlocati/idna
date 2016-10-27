<?php

namespace MLocati\IDNA\Exception;

/**
 * Exception thrown when an invalid code point is met.
 */
class InvalidCodepoint extends Exception
{
    /**
     * The invalid code point.
     *
     * @var mixed
     */
    protected $codepoint;

    /**
     * @param mixed $codepoint The invalid code point
     */
    public function __construct($codepoint)
    {
        $this->codepoint = $codepoint;
        $str = @strval($codepoint);
        $message = 'Invalid code point';
        if ($str !== '') {
            $message .= ': '.$str;
        }
        parent::__construct($message);
    }

    /**
     * Get the invalid code point.
     *
     * @return mixed
     */
    public function getCodepoint()
    {
        return $this->codepoint;
    }
}
