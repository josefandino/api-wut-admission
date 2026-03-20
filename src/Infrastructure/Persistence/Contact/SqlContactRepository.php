<?php

namespace App\Infrastructure\Persistence\Contact;

use App\Domain\Contact\Contact;
use App\Domain\Contact\ContactNotFoundException;
use App\Domain\Contact\ContactRepository;
use App\Shared\Database;
use PDO;

class SqlContactRepository implements ContactRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM contact ORDER BY created_at DESC");
        $rows = $stmt->fetchAll();
        
        return array_map(fn($row) => Contact::fromArray($row), $rows);
    }

    public function findById(string $id): ?Contact
    {
        $stmt = $this->db->prepare("SELECT * FROM contact WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if (!$row) {
            return null;
        }
        
        return Contact::fromArray($row);
    }

    public function save(Contact $contact): Contact
    {
        $sql = "INSERT INTO contact (id, name, lastname, phone, email, affair, message) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $contact->id,
            $contact->name,
            $contact->lastname,
            $contact->phone,
            $contact->email,
            $contact->affair,
            $contact->message
        ]);

        return $contact;
    }
}
