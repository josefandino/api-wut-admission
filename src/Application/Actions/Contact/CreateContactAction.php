<?php

namespace App\Application\Actions\Contact;

use App\Application\Actions\Action;
use App\Domain\Contact\Contact;
use App\Infrastructure\Persistence\Contact\SqlContactRepository;
use App\Shared\Sanitizer;
use Ramsey\Uuid\Uuid;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class CreateContactAction extends Action
{
    private SqlContactRepository $repository;

    public function __construct()
    {
        $this->repository = new SqlContactRepository();
    }

    public static function rules(): array
    {
        return [
            'name' => v::stringType()->notBlank()->length(3, 100),
            'lastname' => v::stringType()->notBlank()->length(3, 100),
            'phone' => v::stringType()->notBlank()->length(7, 20),
            'email' => v::stringType()->notBlank()->email()->length(5, 150),
            'affair' => v::stringType()->notBlank()->length(3, 100),
            'message' => v::stringType()->notBlank()->length(5, 200),
        ];
    }

    public static function allowedFields(): array
    {
        return ['name', 'lastname', 'email', 'phone', 'affair', 'message'];
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
            'email' => 'email',
            'phone' => 'phone',
            'affair' => 'string',
            'message' => 'string',
        ];
        $formData = Sanitizer::sanitizeArray($formData, $sanitizationRules);

        // Validar que los datos sean seguros
        foreach (['name', 'lastname', 'email', 'phone', 'affair', 'message'] as $field) {
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
            $contact = new Contact(
                id: Uuid::uuid4()->toString(),
                name: trim($formData['name']),
                lastname: trim($formData['lastname']),
                phone: trim($formData['phone']),
                email: trim($formData['email']),
                affair: trim($formData['affair']),
                message: trim($formData['message'])
            );

            $this->repository->save($contact);

            error_log("New contact created - ID: {$contact->id}, Email: {$contact->email}, IP: " . $this->getClientIp());

            return $this->respondWithData([
                'id' => $contact->id,
                'mensaje' => 'Contacto creado exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return $this->respondWithError('Error al crear el contacto: ' . $e->getMessage(), 500);
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
