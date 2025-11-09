<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\Post;
use App\Exception\PostPermissionException;

interface PostAuthorizationServiceInterface
{
    /**
     * @throws PostPermissionException
     */
    public function ensureUserCanModifyPost(Post $post, ?User $user): void;
}
