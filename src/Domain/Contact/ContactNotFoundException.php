<?php

namespace App\Domain\Contact;

use Exception;

class ContactNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Contacto no encontrado con ID: {$id}");
    }
}
