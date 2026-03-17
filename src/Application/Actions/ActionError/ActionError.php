<?php

namespace App\Application\Actions\ActionError;

class ActionError
{
    public const BAD_REQUEST = 'BAD_REQUEST';
    public const INSUFFICIENT_PRIVILEGES = 'INSUFFICIENT_PRIVILEGES';
    public const NOT_FOUND = 'NOT_FOUND';
    public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const SERVER_ERROR = 'SERVER_ERROR';
    public const UNAUTHORIZED = 'UNAUTHORIZED';
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';
    public const CONFLICT = 'CONFLICT';

    private string $type;
    private string $description;
    private ?string $message;

    public function __construct(string $type, string $description, ?string $message = null)
    {
        $this->type = $type;
        $this->description = $description;
        $this->message = $message;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
