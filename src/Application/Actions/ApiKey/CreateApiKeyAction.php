<?php

namespace App\Application\Actions\ApiKey;

use App\Application\Actions\Action;
use App\Shared\Database;
use Ramsey\Uuid\Uuid;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class CreateApiKeyAction extends Action
{
    public static function rules(): array
    {
        return [
            'key_name' => v::stringType()->notBlank()->length(3, 100),
            'client_name' => v::optional(v::stringType()->length(3, 150)),
            'client_email' => v::optional(v::stringType()->email()),
            'description' => v::optional(v::stringType()->length(5, 500)),
            'rate_limit' => v::optional(v::intType()->positive()),
            'allowed_endpoints' => v::optional(v::stringType()),
            'allowed_methods' => v::optional(v::stringType()),
            'expires_in_days' => v::optional(v::intType()->positive()),
        ];
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

        $errors = $this->validate($formData);
        
        if (!empty($errors)) {
            return $this->respondWithError('Error de validación', 422, $errors);
        }

        try {
            // Generar API Key única
            $apiKey = self::generateApiKey();

            // Calcular fecha de expiración
            $expiresAt = null;
            if (isset($formData['expires_in_days']) && $formData['expires_in_days'] > 0) {
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$formData['expires_in_days']} days"));
            }

            // Insertar API Key
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO api_keys (
                    api_key, key_name, description,
                    client_name, client_email,
                    rate_limit,
                    allowed_endpoints, allowed_methods,
                    expires_at, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $apiKey,
                trim($formData['key_name']),
                isset($formData['description']) ? trim($formData['description']) : null,
                isset($formData['client_name']) ? trim($formData['client_name']) : null,
                isset($formData['client_email']) ? trim($formData['client_email']) : null,
                $formData['rate_limit'] ?? 100,
                isset($formData['allowed_endpoints']) ? trim($formData['allowed_endpoints']) : '*',
                isset($formData['allowed_methods']) ? trim($formData['allowed_methods']) : 'GET,POST,PUT,DELETE',
                $expiresAt,
                'ADMIN' // En producción, usar usuario autenticado
            ]);

            error_log("API Key creada: {$apiKey} por ADMIN desde IP: " . $this->getClientIp());

            return $this->respondWithCreated('API Key creada exitosamente');
        } catch (\Exception $e) {
            return $this->respondWithError('Error al crear API Key: ' . $e->getMessage(), 500);
        }
    }

    private function validate(array $data): array
    {
        $errors = [];
        
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

    /**
     * Genera una API Key única y segura
     */
    private static function generateApiKey(): string
    {
        // Formato: sk_[hash_aleatorio]
        $random = bin2hex(random_bytes(32));
        $apiKey = 'sk_' . $random;

        // Verificar que sea única
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT id FROM api_keys WHERE api_key = ?");
            $stmt->execute([$apiKey]);

            if ($stmt->fetch()) {
                // Si existe, generar otra (muy improbable)
                return self::generateApiKey();
            }
        } catch (\Exception $e) {
            // Si hay error, generar de todas formas
        }

        return $apiKey;
    }
}
