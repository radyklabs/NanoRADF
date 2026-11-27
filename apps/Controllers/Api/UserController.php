<?php declare (strict_types=1);

/**
 * User API controller — CRUD for users.
 *
 * @package     NanoRADF
 * @author      RADLabs Development Team
 * @copyright   Radlabs Studio (c) 1986 - 2026
 * @since       0.0.1
 */

namespace App\Controllers\Api;

use System\Controllers\ApiController;

if (!defined ('basepath'))
{
    try {exit (@require dirname (__DIR__, 3).DIRECTORY_SEPARATOR.'.errors');}
    catch (Throwable $e){$file = basename(__FILE__); exit("404 Forbidden {$file}");}
}

final class UserController extends ApiController
{
    /**
     * GET /api/v1/users — list users.
     *
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function list (): array
    {
        return $this->ok([
            'users' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ],
            'total' => 2,
        ]);
    }

    /**
     * GET /api/v1/users/{id} — show single user.
     *
     * @param string $id User ID
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function show (string $id): array
    {
        $userId = (int) $id;
        if ($userId < 1 || $userId > 2) {
            return $this->notFound("User {$id} not found");
        }

        return $this->ok([
            'id'    => $userId,
            'name'  => $userId === 1 ? 'John Doe' : 'Jane Smith',
            'email' => $userId === 1 ? 'john@example.com' : 'jane@example.com',
        ]);
    }

    /**
     * POST /api/v1/users — create user.
     *
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function create (): array
    {
        return $this->ok(['id' => 3, 'message' => 'User created'], 201);
    }

    /**
     * PUT /api/v1/users/{id} — update user.
     *
     * @param string $id User ID
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function update (string $id): array
    {
        return $this->ok(['id' => (int) $id, 'message' => 'User updated']);
    }

    /**
     * DELETE /api/v1/users/{id} — delete user.
     *
     * @param string $id User ID
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function delete (string $id): array
    {
        return $this->ok(['id' => (int) $id, 'message' => 'User deleted']);
    }
}