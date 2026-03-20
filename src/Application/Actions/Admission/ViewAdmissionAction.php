<?php

namespace App\Application\Actions\Admission;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\Admission\SqlAdmissionRepository;

class ViewAdmissionAction extends Action
{
    private SqlAdmissionRepository $repository;

    public function __construct()
    {
        $this->repository = new SqlAdmissionRepository();
    }

    protected function action(): \Psr\Http\Message\ResponseInterface
    {
        $id = $this->args['id'];

        try {
            $admission = $this->repository->findById($id);
            
            if (!$admission) {
                return $this->respondWithError('Admisión no encontrada', 404);
            }
            
            return $this->respondWithData($admission->toArray());
        } catch (\Exception $e) {
            return $this->respondWithError('Error al obtener la admisión: ' . $e->getMessage(), 500);
        }
    }
}
