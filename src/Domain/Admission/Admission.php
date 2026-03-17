<?php

namespace App\Domain\Admission;

class Admission
{
    public string $id;
    public string $name;
    public string $lastname;
    public string $typeDocument;
    public string $document;
    public ?string $phone;
    public string $email;
    public ?string $country;
    public ?string $city;
    public ?string $address;
    public ?string $program;
    public ?string $createdAt;

    public function __construct(
        string $id,
        string $name,
        string $lastname,
        string $typeDocument,
        string $document,
        ?string $phone,
        string $email,
        ?string $country,
        ?string $city,
        ?string $address,
        ?string $program,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->lastname = $lastname;
        $this->typeDocument = $typeDocument;
        $this->document = $document;
        $this->phone = $phone;
        $this->email = $email;
        $this->country = $country;
        $this->city = $city;
        $this->address = $address;
        $this->program = $program;
        $this->createdAt = $createdAt;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            name: $data['name'] ?? '',
            lastname: $data['lastname'] ?? '',
            typeDocument: $data['type_document'] ?? '',
            document: $data['document'] ?? '',
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? '',
            country: $data['country'] ?? null,
            city: $data['city'] ?? null,
            address: $data['address'] ?? null,
            program: $data['program'] ?? null,
            createdAt: $data['created_at'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'lastname' => $this->lastname,
            'type_document' => $this->typeDocument,
            'document' => $this->document,
            'phone' => $this->phone,
            'email' => $this->email,
            'country' => $this->country,
            'city' => $this->city,
            'address' => $this->address,
            'program' => $this->program,
            'created_at' => $this->createdAt
        ];
    }
}
