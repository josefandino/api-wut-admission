<?php

namespace App\Domain\Admission;

use Exception;

class AdmissionNotFoundException extends Exception
{
    public function __construct(string $id)
    {
        parent::__construct("Admisión no encontrada con ID: {$id}");
    }
}
