<?php

namespace App\Presentation\Routes;

use Slim\App;
use App\Application\Actions\User\CreateUserAction;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Actions\User\UpdateUserAction;
use App\Application\Actions\User\DeleteUserAction;

class UserRoutes
{
    public static function register(App $app, string $jwtSecret): void
    {
        // POST /admin/users - Crear usuario (requiere JWT - admin)
        $app->post('/admin/users', CreateUserAction::class);

        // GET /admin/users - Listar usuarios (requiere JWT - admin)
        $app->get('/admin/users', ListUsersAction::class);

        // GET /admin/users/{id} - Ver usuario específico (requiere JWT - admin)
        $app->get('/admin/users/{id}', ViewUserAction::class);

        // PUT /admin/users/{id} - Actualizar usuario (requiere JWT - admin)
        $app->put('/admin/users/{id}', UpdateUserAction::class);

        // DELETE /admin/users/{id} - Eliminar usuario (requiere JWT - admin)
        $app->delete('/admin/users/{id}', DeleteUserAction::class);
    }
}
