<?php

namespace App\Application\Actions\Admission;

use App\Application\Actions\Action;
use App\Domain\Admission\Admission;
use App\Infrastructure\Persistence\Admission\SqlAdmissionRepository;
use App\Shared\Sanitizer;
use Ramsey\Uuid\Uuid;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class CreateAdmissionAction extends Action
{
    private SqlAdmissionRepository $repository;

    public function __construct()
    {
        $this->repository = new SqlAdmissionRepository();
    }

    public static function rules(): array
    {
        return [
            'name' => v::stringType()->notBlank()->length(3, 100),
            'lastname' => v::stringType()->notBlank()->length(3, 100),
            'type_document' => v::stringType()->notBlank()->length(1, 50),
            'document' => v::stringType()->notBlank()->length(5, 25),
            'email' => v::stringType()->notBlank()->email()->length(5, 150),
            'phone' => v::optional(v::stringType()->notBlank()->length(7, 20)),
            'country' => v::optional(v::stringType()->notBlank()->length(2, 100)),
            'city' => v::optional(v::stringType()->notBlank()->length(2, 100)),
            'address' => v::optional(v::stringType()->notBlank()->length(5, 500)),
            'program' => v::optional(v::stringType()->notBlank()->length(3, 150)),
        ];
    }

    public static function allowedFields(): array
    {
        return ['name', 'lastname', 'type_document', 'document', 'email', 'phone', 'country', 'city', 'address', 'program'];
    }

    protected function action(): \Psr\Http\Message\ResponseInterface
    {
        $formData = $this->getFormData();
        
        if (empty($formData)) {
            $formData = $this->getJsonData();
        }

        if (empty($formData)) {
            return $this->respondWithError('No se recibieron datos', 400);
        }

        // Sanitizar datos de entrada
        $sanitizationRules = [
            'name' => 'string',
            'lastname' => 'string',
            'type_document' => 'string',
            'document' => 'string',
            'email' => 'email',
            'phone' => 'phone',
            'country' => 'string',
            'city' => 'string',
            'address' => 'string',
            'program' => 'string',
        ];
        $formData = Sanitizer::sanitizeArray($formData, $sanitizationRules);

        // Validar que los datos sean seguros
        $fieldsToCheck = ['name', 'lastname', 'type_document', 'document', 'email', 'phone', 'country', 'city', 'address', 'program'];
        foreach ($fieldsToCheck as $field) {
            if (isset($formData[$field]) && !Sanitizer::isSafe($formData[$field])) {
                error_log("Potential security threat detected in field '{$field}' - IP: " . $this->getClientIp());
                return $this->respondWithError('Datos inválidos detectados', 400);
            }
        }

        $errors = $this->validate($formData);
        
        if (!empty($errors)) {
            return $this->respondWithError('Error de validación', 422, $errors);
        }

        try {
            $admission = new Admission(
                id: Uuid::uuid4()->toString(),
                name: trim($formData['name']),
                lastname: trim($formData['lastname']),
                typeDocument: trim($formData['type_document']),
                document: trim($formData['document']),
                phone: isset($formData['phone']) ? trim($formData['phone']) : null,
                email: trim($formData['email']),
                country: isset($formData['country']) ? trim($formData['country']) : null,
                city: isset($formData['city']) ? trim($formData['city']) : null,
                address: isset($formData['address']) ? trim($formData['address']) : null,
                program: isset($formData['program']) ? trim($formData['program']) : null
            );

            $this->repository->save($admission);

            error_log("New admission created - ID: {$admission->id}, Document: {$admission->document}, IP: " . $this->getClientIp());

            return $this->respondWithData([
                'id' => $admission->id,
                'mensaje' => 'Admisión creada exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return $this->respondWithError('Error al crear la admisión: ' . $e->getMessage(), 500);
        }
    }

    private function validate(array $data): array
    {
        $errors = [];

        // Validar campos desconocidos
        $allowedFields = self::allowedFields();
        foreach ($data as $field => $value) {
            if (!in_array($field, $allowedFields)) {
                $errors[$field] = "Campo no permitido";
            }
        }
        
        // Validar campos definidos
        foreach (self::rules() as $field => $validator) {
            try {
                $value = $data[$field] ?? null;
                $validator->assert($value);
            } catch (NestedValidationException $exception) {
                $messages = $exception->getMessages();
                $errors[$field] = is_array($messages) ? reset($messages) : $messages;
            }
        }

        return $errors;
    }
}
