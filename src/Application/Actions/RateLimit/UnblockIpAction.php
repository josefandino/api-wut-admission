<?php

namespace App\Application\Actions\RateLimit;

use App\Application\Actions\Action;
use App\Shared\RateLimiter;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class UnblockIpAction extends Action
{
    public static function rules(): array
    {
        return [
            'ip' => v::stringType()->notBlank()->ip(),
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
            $ip = trim($formData['ip']);
            $endpoint = isset($formData['endpoint']) ? trim($formData['endpoint']) : null;

            $success = RateLimiter::unblock($ip, $endpoint);

            if ($success) {
                error_log("IP desbloqueada: {$ip} por admin desde IP: " . $this->getClientIp());
                
                return $this->respondWithData([
                    'mensaje' => 'IP desbloqueada exitosamente',
                    'ip' => $ip,
                    'endpoint' => $endpoint
                ], 200);
            } else {
                return $this->respondWithError('Error al desbloquear la IP', 500);
            }
        } catch (\Exception $e) {
            return $this->respondWithError('Error: ' . $e->getMessage(), 500);
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
}
