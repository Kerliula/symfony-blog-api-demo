<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Exception\PostPermissionException;
use App\Service\PostAuthorizationService;
use PHPUnit\Framework\TestCase;

class PostAuthorizationServiceTest extends TestCase
{
    private PostAuthorizationService $service;

    protected function setUp(): void
    {
        $this->service = new PostAuthorizationService();
    }

    public function testEnsureUserCanModifyPostAllowsOwner(): void
    {
        $user = new User();
        $post = new Post();
        $post->setOwner($user);

        $this->service->ensureUserCanModifyPost($post, $user);

        $this->assertTrue(true);
    }

    public function testEnsureUserCanModifyPostThrowsForNonOwner(): void
    {
        $owner = new User();
        $otherUser = new User();

        $post = new Post();
        $post->setOwner($owner);

        $this->expectException(PostPermissionException::class);
        $this->expectExceptionMessage('You do not have permission to modify this post');

        $this->service->ensureUserCanModifyPost($post, $otherUser);
    }

    public function testEnsureUserCanModifyPostThrowsForAnonymousUser(): void
    {
        $owner = new User();
        $post = new Post();
        $post->setOwner($owner);

        $this->expectException(PostPermissionException::class);

        $this->service->ensureUserCanModifyPost($post, null);
    }
}
