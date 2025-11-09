<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreatePostRequest;
use App\Dto\UpdatePostRequest;
use App\Entity\Post;
use App\Entity\User;
use App\Exception\PostNotFoundException;
use App\Repository\PostRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class PostService implements PostServiceInterface
{
    public function __construct(
        private readonly PostRepository $postRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getPaginatedPosts(int $page, int $limit, ?string $search = null): array
    {
        $offset = ($page - 1) * $limit;

        $posts = $this->postRepository->findPaginated($offset, $limit, $search);
        $total = $this->postRepository->countAll($search);

        return [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'posts' => array_map(fn(Post $post) => $post->toArray(), $posts),
        ];
    }

    public function getPostByIdOrFail(int $id): Post
    {
        $post = $this->postRepository->find($id);

        if (!$post) {
            throw new PostNotFoundException("Post with ID {$id} not found");
        }

        return $post;
    }

    public function createPost(CreatePostRequest $request, User $owner): Post
    {
        $post = new Post();
        $post->setTitle($request->getTitle());
        $post->setContent($request->getContent());
        $post->setOwner($owner);
        $post->setCreatedAt(new DateTimeImmutable());
        $post->setUpdatedAt(new DateTimeImmutable());

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }

    public function updatePost(Post $post, UpdatePostRequest $request): Post
    {
        $post->setTitle($request->getTitle());
        $post->setContent($request->getContent());
        $post->setUpdatedAt(new DateTimeImmutable());

        $this->entityManager->flush();

        return $post;
    }

    public function deletePost(Post $post): void
    {
        $this->entityManager->remove($post);
        $this->entityManager->flush();
    }
}
