<?php

namespace MLocati\IDNA\Exception;

class InvalidParameter extends Exception
{
    protected $function;
    protected $parameterName;
    public function __construct($function, $parameterName, $message = '')
    {
        $this->function = $function;
        $this->parameterName = $parameterName;
        $message = "Invalid parameter $parameterName in $function".(($message === '') ? '' : ":\n$message");
        parent::__construct($message);
    }
    public function getFunction()
    {
        return $this->function;
    }
    public function getParameterName()
    {
        return $this->parameterName;
    }
}
