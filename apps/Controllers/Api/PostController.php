<?php declare (strict_types=1);

/**
 * Post API controller — CRUD for posts.
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

final class PostController extends ApiController
{
    /**
     * GET /api/v1/posts — list posts.
     *
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function list (): array
    {
        return $this->ok([
            'posts' => [
                ['id' => 1, 'title' => 'First Post', 'content' => 'Hello world!', 'user_id' => 1],
                ['id' => 2, 'title' => 'Second Post', 'content' => 'Another post', 'user_id' => 2],
            ],
            'total' => 2,
        ]);
    }

    /**
     * GET /api/v1/posts/{id} — show single post.
     *
     * @param string $id Post ID
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function show (string $id): array
    {
        $postId = (int) $id;
        if ($postId < 1 || $postId > 2) {
            return $this->notFound("Post {$id} not found");
        }

        return $this->ok([
            'id'      => $postId,
            'title'   => $postId === 1 ? 'First Post' : 'Second Post',
            'content' => $postId === 1 ? 'Hello world!' : 'Another post',
            'user_id' => $postId,
        ]);
    }

    /**
     * POST /api/v1/posts — create post.
     *
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function create (): array
    {
        return $this->ok(['id' => 3, 'message' => 'Post created'], 201);
    }

    /**
     * PUT /api/v1/posts/{id} — update post.
     *
     * @param string $id Post ID
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function update (string $id): array
    {
        return $this->ok(['id' => (int) $id, 'message' => 'Post updated']);
    }

    /**
     * DELETE /api/v1/posts/{id} — delete post.
     *
     * @param string $id Post ID
     * @return array{status:int, body:string, headers:list<string>}
     */
    public function delete (string $id): array
    {
        return $this->ok(['id' => (int) $id, 'message' => 'Post deleted']);
    }
}