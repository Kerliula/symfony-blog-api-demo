<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreatePostRequest;
use App\Dto\UpdatePostRequest;
use App\Entity\Post;
use App\Entity\User;
use App\Exception\PostNotFoundException;

interface PostServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getPaginatedPosts(int $page, int $limit, ?string $search = null): array;
    /**
     * @throws PostNotFoundException
     */
    public function getPostByIdOrFail(int $id): Post;
    public function createPost(CreatePostRequest $request, User $owner): Post;
    public function updatePost(Post $post, UpdatePostRequest $request): Post;
    public function deletePost(Post $post): void;
}
