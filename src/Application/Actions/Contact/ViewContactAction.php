<?php

namespace App\Application\Actions\Contact;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\Contact\SqlContactRepository;

class ViewContactAction extends Action
{
    private SqlContactRepository $repository;

    public function __construct()
    {
        $this->repository = new SqlContactRepository();
    }

    protected function action(): \Psr\Http\Message\ResponseInterface
    {
        $id = $this->args['id'];

        try {
            $contact = $this->repository->findById($id);
            
            if (!$contact) {
                return $this->respondWithError('Contacto no encontrado', 404);
            }
            
            return $this->respondWithData($contact->toArray());
        } catch (\Exception $e) {
            return $this->respondWithError('Error al obtener el contacto: ' . $e->getMessage(), 500);
        }
    }
}

