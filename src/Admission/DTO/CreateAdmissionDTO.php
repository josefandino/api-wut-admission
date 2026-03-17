<?php

namespace App\Admission\DTO;

use Respect\Validation\Validator as v;

class CreateAdmissionDTO
{
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

    public static function rules(): array
    {
        return [
            'name' => v::notEmpty()->length(1, 100),
            'lastname' => v::notEmpty()->length(1, 100),
            'type_document' => v::notEmpty()->length(1, 50),
            'document' => v::notEmpty()->length(1, 25),
            'phone' => v::optional(v::length(1, 20)),
            'email' => v::notEmpty()->email()->length(1, 150),
            'country' => v::optional(v::length(1, 100)),
            'city' => v::optional(v::length(1, 100)),
            'address' => v::optional(v::length(null, 500)),
            'program' => v::optional(v::length(1, 150)),
        ];
    }

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->name = trim($data['name'] ?? '');
        $dto->lastname = trim($data['lastname'] ?? '');
        $dto->typeDocument = trim($data['type_document'] ?? '');
        $dto->document = trim($data['document'] ?? '');
        $dto->phone = isset($data['phone']) ? trim($data['phone']) : null;
        $dto->email = trim($data['email'] ?? '');
        $dto->country = isset($data['country']) ? trim($data['country']) : null;
        $dto->city = isset($data['city']) ? trim($data['city']) : null;
        $dto->address = isset($data['address']) ? trim($data['address']) : null;
        $dto->program = isset($data['program']) ? trim($data['program']) : null;
        
        return $dto;
    }
}
