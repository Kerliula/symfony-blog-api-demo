<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\Post;
use App\Entity\User;
use App\Service\PostServiceInterface;
use App\Service\PostAuthorizationServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PostControllerTest extends WebTestCase
{
    private $client;
    private $postServiceMock;
    private $authServiceMock;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->postServiceMock = $this->createMock(PostServiceInterface::class);
        $this->authServiceMock = $this->createMock(PostAuthorizationServiceInterface::class);

        $container = $this->client->getContainer();
        $container->set(PostServiceInterface::class, $this->postServiceMock);
        $container->set(PostAuthorizationServiceInterface::class, $this->authServiceMock);

        $mockUser = $this->createMock(User::class);
        $mockUser->method('getId')->willReturn(1);
        $this->client->loginUser($mockUser);
    }

    public function testIndexReturnsPaginatedPosts(): void
    {
        $post = $this->createMock(Post::class);
        $post->method('toArray')->willReturn(['id' => 1, 'title' => 'Test']);

        $this->postServiceMock->method('getPaginatedPosts')
            ->willReturn([
                'current_page' => 1,
                'per_page' => 10,
                'total' => 1,
                'posts' => [$post->toArray()],
            ]);

        $this->client->request('GET', '/api/posts');

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals(1, $data['current_page']);
        $this->assertCount(1, $data['posts']);
    }

    public function testShowReturnsPost(): void
    {
        $post = $this->createMock(Post::class);
        $post->method('toArray')->willReturn(['id' => 1, 'title' => 'Test']);

        $this->postServiceMock->method('getPostByIdOrFail')->willReturn($post);

        $this->client->request('GET', '/api/posts/1');

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals(1, $data['id']);
    }

    public function testShowReturnsNotFound(): void
    {
        $this->postServiceMock->method('getPostByIdOrFail')
            ->willThrowException(new \App\Exception\PostNotFoundException('Post not found'));

        $this->client->request('GET', '/api/posts/999');

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testUpdatePostReturnsSuccess(): void
    {
        $post = $this->createMock(Post::class);
        $post->method('toArray')->willReturn(['id' => 1, 'title' => 'Updated']);

        $this->postServiceMock->method('getPostByIdOrFail')->willReturn($post);
        $this->postServiceMock->method('updatePost')->willReturn($post);

        $this->authServiceMock
            ->method('ensureUserCanModifyPost')
            ->willReturnCallback(fn() => null);

        $payload = ['title' => 'Updated', 'content' => 'Updated content'];

        $this->client->request(
            'PUT',
            '/api/posts/update/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Post updated successfully', $data['message']);
    }

    public function testDeletePostReturnsSuccess(): void
    {
        $post = $this->createMock(Post::class);

        $this->postServiceMock->method('getPostByIdOrFail')->willReturn($post);
        $this->postServiceMock->method('deletePost')->willReturnCallback(fn() => null);

        $this->authServiceMock
            ->method('ensureUserCanModifyPost')
            ->willReturnCallback(fn() => null);

        $this->client->request('DELETE', '/api/posts/1');

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Post deleted successfully', $data['message']);
    }
}
