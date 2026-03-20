<?php

namespace App\Domain\Admission;

interface AdmissionRepository
{
    public function findAll(): array;
    public function findById(string $id): ?Admission;
    public function findByDocument(string $document): ?Admission;
    public function save(Admission $admission): Admission;
}
