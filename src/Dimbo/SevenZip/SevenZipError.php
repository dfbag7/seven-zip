<?php namespace Dimbo\SevenZip;


class SevenZipError extends \RuntimeException
{
    function __construct($code, $message = null)
    {
        parent::__construct($message, $code);
    }
}
