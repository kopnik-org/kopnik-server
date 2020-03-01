<?php

declare(strict_types=1);

namespace VK\Client;

use VK\Actions\Messages;

class VKApiClient
{
    /**
     * Constructor.
     */
    public function __construct()
    {

    }

    public function messages()
    {
        return new Messages();
    }
}
