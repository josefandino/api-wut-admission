<?php

namespace App\Domain\User;

use Exception;

class UserNotFoundException extends Exception
{
    public function __construct(int $id)
    {
        parent::__construct("User with ID {$id} not found.");
    }
}
