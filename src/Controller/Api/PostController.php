<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\CreatePostRequest;
use App\Dto\UpdatePostRequest;
use App\Entity\Post;
use App\Exception\PostCreationException;
use App\Exception\PostNotFoundException;
use App\Exception\PostPermissionException;
use App\Exception\PostValidationException;
use App\Repository\PostRepository;
use App\Service\PostAuthorizationServiceInterface;
use App\Service\PostServiceInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/posts', name: 'api_posts_')]
final class PostController extends AbstractController
{
    private const INDEX_CACHE_MAX_AGE_SECONDS = 30;
    private const INDEX_DEFAULT_POSTS_PER_PAGE = 10;
    private const INDEX_MAX_POSTS_PER_PAGE = 100;
    private const INDEX_DEFAULT_PAGE_NUMBER = 1;
    private const QUERY_PARAMETER_PAGE = 'page';
    private const QUERY_PARAMETER_LIMIT = 'limit';
    private const QUERY_SEARCH = 'search';
    private const FORMAT = 'json';

    public function __construct(
        private readonly PostServiceInterface $postService,
        private readonly PostRepository $postRepository,
        private readonly SerializerInterface $serializer,
        private readonly PostAuthorizationServiceInterface $authorizationService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[Cache(smaxage: self::INDEX_CACHE_MAX_AGE_SECONDS)]
    public function index(Request $request): JsonResponse
    {
        $page = max(self::INDEX_DEFAULT_PAGE_NUMBER, (int) $request->query->get(
            self::QUERY_PARAMETER_PAGE,
            self::INDEX_DEFAULT_PAGE_NUMBER
        ));

        $limit = min(self::INDEX_MAX_POSTS_PER_PAGE, max(
            1,
            (int) $request->query->get(self::QUERY_PARAMETER_LIMIT, self::INDEX_DEFAULT_POSTS_PER_PAGE)
        ));

        $search = $request->query->get(self::QUERY_SEARCH);
        $result = $this->postService->getPaginatedPosts($page, $limit, $search);

        return $this->json($result);
    }

    #[Route('/my', name: 'my_posts', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function myPosts(): JsonResponse
    {
        $posts = $this->postRepository->findByOwner($this->getUser());

        return $this->json([
            'posts' => array_map(fn(Post $post) => $post->toArray(), $posts),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        try {
            $post = $this->postService->getPostByIdOrFail($id);

            return $this->json($post->toArray());
        } catch (PostNotFoundException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_NOT_FOUND
            );
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(int $id): JsonResponse
    {
        try {
            $post = $this->postService->getPostByIdOrFail($id);

            $this->authorizationService->ensureUserCanModifyPost($post, $this->getUser());

            $this->postService->deletePost($post);

            return $this->json(['message' => 'Post deleted successfully']);
        } catch (PostNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (PostPermissionException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (Exception $e) {
            return $this->json(['error' => 'Failed to delete post'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(Request $request): JsonResponse
    {
        try {
            $createRequest = $this->deserializeAndValidate(
                $request->getContent(),
                CreatePostRequest::class
            );

            $post = $this->postService->createPost($createRequest, $this->getUser());

            return $this->json(
                ['message' => 'Post created successfully', 'post' => $post->toArray()],
                Response::HTTP_CREATED
            );
        } catch (PostValidationException | PostCreationException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->json(['error' => 'Failed to create post'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/update/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $post = $this->postService->getPostByIdOrFail($id);

            $this->authorizationService->ensureUserCanModifyPost($post, $this->getUser());

            $updateRequest = $this->deserializeAndValidate(
                $request->getContent(),
                UpdatePostRequest::class
            );

            $post = $this->postService->updatePost($post, $updateRequest);

            return $this->json([
                'message' => 'Post updated successfully',
                'post' => $post->toArray(),
            ]);
        } catch (PostNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (PostPermissionException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (PostValidationException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->json(['error' => 'Failed to update post'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function deserializeAndValidate(string $content, string $class): object
    {
        $dto = $this->serializer->deserialize($content, $class, self::FORMAT);

        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            throw new ValidationFailedException($dto, $errors);
        }

        return $dto;
    }
}
