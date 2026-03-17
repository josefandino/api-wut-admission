<?php

namespace App\Admission\Validation;

use Respect\Validation\Exceptions\NestedValidationException;

class DTOValidator
{
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $validator) {
            try {
                $value = $data[$field] ?? null;
                $validator->assert($value);
            } catch (NestedValidationException $exception) {
                $errors[$field] = $exception->getMessages();
            }
        }

        return $errors;
    }

    public static function isValid(array $data, array $rules): bool
    {
        return empty(self::validate($data, $rules));
    }
}
