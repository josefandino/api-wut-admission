<?php

namespace App\Application\Actions\Contact;

use App\Application\Actions\Action;
use App\Domain\Contact\ContactRepository;
use App\Infrastructure\Persistence\Contact\SqlContactRepository;

class ListContactAction extends Action
{
    private ContactRepository $repository;

    public function __construct()
    {
        $this->repository = new SqlContactRepository();
    }

    protected function action(): \Psr\Http\Message\ResponseInterface
    {
        error_log("Contact list access - IP: " . $this->getClientIp());

        try {
            $contacts = $this->repository->findAll();
            
            return $this->respondWithData([
                'contacts' => array_map(fn($c) => $c->toArray(), $contacts)
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error al obtener los contactos: ' . $e->getMessage(), 500);
        }
    }
}

