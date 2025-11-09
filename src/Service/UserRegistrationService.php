<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\SignupRequest;
use App\Entity\User;
use App\Exception\UserAlreadyExistsException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationService implements UserRegistrationServiceInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }


    public function registerUser(SignupRequest $signupRequest): User
    {
        $this->ensureUserDoesNotExist($signupRequest->getEmail());

        $user = $this->createUser($signupRequest);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function ensureUserDoesNotExist(string $email): void
    {
        if ($this->userRepository->findOneBy(['email' => $email])) {
            throw new UserAlreadyExistsException(
                sprintf('User with email "%s" already exists', $email)
            );
        }
    }

    private function createUser(SignupRequest $signupRequest): User
    {
        $user = new User();
        $user->setEmail($signupRequest->getEmail());
        $user->setRoles(['ROLE_USER']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $signupRequest->getPassword())
        );

        return $user;
    }
}
