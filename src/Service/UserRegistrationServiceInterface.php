<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\SignupRequest;
use App\Entity\User;
use App\Exception\UserAlreadyExistsException;

interface UserRegistrationServiceInterface
{
    /**
     * @throws UserAlreadyExistsException
     */
    public function registerUser(SignupRequest $signupRequest): User;
}
