<?php

namespace App\Domain\Contact;

class Contact
{
    public string $id;
    public string $name;
    public string $lastname;
    public string $phone;
    public string $email;
    public string $affair;
    public string $message;
    public ?string $createdAt;

    public function __construct(
        string $id,
        string $name,
        string $lastname,
        string $phone,
        string $email,
        string $affair,
        string $message,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->lastname = $lastname;
        $this->phone = $phone;
        $this->email = $email;
        $this->affair = $affair;
        $this->message = $message;
        $this->createdAt = $createdAt;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            name: $data['name'] ?? '',
            lastname: $data['lastname'] ?? '',
            phone: $data['phone'] ?? '',
            email: $data['email'] ?? '',
            affair: $data['affair'] ?? '',
            message: $data['message'] ?? '',
            createdAt: $data['created_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'lastname' => $this->lastname,
            'phone' => $this->phone,
            'email' => $this->email,
            'affair' => $this->affair,
            'message' => $this->message,
            'created_at' => $this->createdAt
        ];
    }
}
