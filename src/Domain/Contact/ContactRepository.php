<?php

namespace App\Domain\Contact;

interface ContactRepository
{
    public function findAll(): array;
    public function findById(string $id): ?Contact;
    public function save(Contact $contact): Contact;
}
