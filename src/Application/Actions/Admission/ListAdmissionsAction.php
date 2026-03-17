<?php

namespace App\Application\Actions\Admission;

use App\Application\Actions\Action;
use App\Domain\Admission\AdmissionRepository;
use App\Infrastructure\Persistence\Admission\SqlAdmissionRepository;

class ListAdmissionsAction extends Action
{
    private AdmissionRepository $repository;

    public function __construct()
    {
        $this->repository = new SqlAdmissionRepository();
    }

    protected function action(): \Psr\Http\Message\ResponseInterface
    {
        error_log("Admission list access - IP: " . $this->getClientIp());

        try {
            $admissions = $this->repository->findAll();
            
            return $this->respondWithData([
                'admissions' => array_map(fn($a) => $a->toArray(), $admissions)
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error al obtener las admisiones: ' . $e->getMessage(), 500);
        }
    }
}
