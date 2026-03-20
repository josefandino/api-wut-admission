<?php

namespace App\Application\Actions\ActionError;

class ActionPayload
{
    private int $statusCode;
    private ?array $data;
    private ?ActionError $error;

    public function __construct(
        int $statusCode = 200,
        ?array $data = null,
        ?ActionError $error = null
    ) {
        $this->statusCode = $statusCode;
        $this->data = $data;
        $this->error = $error;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getError(): ?ActionError
    {
        return $this->error;
    }
}
