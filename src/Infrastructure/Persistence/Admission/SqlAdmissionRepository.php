<?php

namespace App\Infrastructure\Persistence\Admission;

use App\Domain\Admission\Admission;
use App\Domain\Admission\AdmissionNotFoundException;
use App\Domain\Admission\AdmissionRepository;
use App\Shared\Database;
use PDO;

class SqlAdmissionRepository implements AdmissionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM admission ORDER BY created_at DESC");
        $rows = $stmt->fetchAll();
        
        return array_map(fn($row) => Admission::fromArray($row), $rows);
    }

    public function findById(string $id): ?Admission
    {
        $stmt = $this->db->prepare("SELECT * FROM admission WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if (!$row) {
            return null;
        }
        
        return Admission::fromArray($row);
    }

    public function findByDocument(string $document): ?Admission
    {
        $stmt = $this->db->prepare("SELECT * FROM admission WHERE document = ?");
        $stmt->execute([$document]);
        $row = $stmt->fetch();
        
        if (!$row) {
            return null;
        }
        
        return Admission::fromArray($row);
    }

    public function save(Admission $admission): Admission
    {
        $sql = "INSERT INTO admission (id, name, lastname, type_document, document, phone, email, country, city, address, program) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $admission->id,
            $admission->name,
            $admission->lastname,
            $admission->typeDocument,
            $admission->document,
            $admission->phone,
            $admission->email,
            $admission->country,
            $admission->city,
            $admission->address,
            $admission->program
        ]);

        return $admission;
    }
}
