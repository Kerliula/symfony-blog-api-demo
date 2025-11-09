<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\CreatePostRequest;
use App\Dto\UpdatePostRequest;
use App\Entity\Post;
use App\Entity\User;
use App\Exception\PostNotFoundException;
use App\Repository\PostRepository;
use App\Service\PostService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class PostServiceTest extends TestCase
{
    private PostRepository $postRepository;
    private EntityManagerInterface $entityManager;
    private PostService $service;

    protected function setUp(): void
    {
        $this->postRepository = $this->createMock(PostRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new PostService($this->postRepository, $this->entityManager);
    }

    public function testGetPaginatedPostsReturnsExpectedArray(): void
    {
        $post = $this->createConfiguredMock(Post::class, [
            'toArray' => ['id' => 1, 'title' => 'Sample', 'content' => 'Content'],
        ]);

        $this->postRepository
            ->expects($this->once())
            ->method('findPaginated')
            ->with(0, 10, null)
            ->willReturn([$post]);

        $this->postRepository
            ->expects($this->once())
            ->method('countAll')
            ->with(null)
            ->willReturn(1);

        $result = $this->service->getPaginatedPosts(1, 10);

        $this->assertEquals(1, $result['current_page']);
        $this->assertEquals(10, $result['per_page']);
        $this->assertEquals(1, $result['total']);
        $this->assertCount(1, $result['posts']);
        $this->assertEquals(['id' => 1, 'title' => 'Sample', 'content' => 'Content'], $result['posts'][0]);
    }

    public function testGetPostByIdOrFailReturnsPostWhenFound(): void
    {
        $post = new Post();

        $this->postRepository
            ->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($post);

        $result = $this->service->getPostByIdOrFail(123);

        $this->assertSame($post, $result);
    }

    public function testGetPostByIdOrFailThrowsWhenNotFound(): void
    {
        $this->postRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(PostNotFoundException::class);
        $this->expectExceptionMessage('Post with ID 999 not found');

        $this->service->getPostByIdOrFail(999);
    }

    public function testCreatePostPersistsNewPost(): void
    {
        $request = $this->createMock(CreatePostRequest::class);
        $request->method('getTitle')->willReturn('Title');
        $request->method('getContent')->willReturn('Content');

        $user = new User();

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $post = $this->service->createPost($request, $user);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals('Title', $post->getTitle());
        $this->assertEquals('Content', $post->getContent());
        $this->assertSame($user, $post->getOwner());
        $this->assertInstanceOf(DateTimeImmutable::class, $post->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $post->getUpdatedAt());
    }

    public function testUpdatePostUpdatesFieldsAndFlushes(): void
    {
        $post = new Post();
        $request = $this->createMock(UpdatePostRequest::class);
        $request->method('getTitle')->willReturn('Updated title');
        $request->method('getContent')->willReturn('Updated content');

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->updatePost($post, $request);

        $this->assertSame($post, $result);
        $this->assertEquals('Updated title', $post->getTitle());
        $this->assertEquals('Updated content', $post->getContent());
        $this->assertInstanceOf(DateTimeImmutable::class, $post->getUpdatedAt());
    }

    public function testDeletePostRemovesAndFlushes(): void
    {
        $post = new Post();

        $this->entityManager->expects($this->once())->method('remove')->with($post);
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->deletePost($post);
    }
}
