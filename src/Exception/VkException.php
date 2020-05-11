<?php

declare(strict_types=1);

namespace App\Exception;

class VkException extends \Exception
{
    protected $error_code;
    protected $error_message;

    /*
    public function __construct($error_code, $error_message)
    {

    }
    */
}
