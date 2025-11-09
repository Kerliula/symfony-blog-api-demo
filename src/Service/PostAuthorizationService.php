<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Post;
use App\Entity\User;
use App\Exception\PostPermissionException;

class PostAuthorizationService implements PostAuthorizationServiceInterface
{
    public function ensureUserCanModifyPost(Post $post, ?User $user): void
    {
        if (!$this->canUserModifyPost($post, $user)) {
            throw new PostPermissionException('You do not have permission to modify this post');
        }
    }

    private function canUserModifyPost(Post $post, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $post->getOwner() === $user;
    }
}
