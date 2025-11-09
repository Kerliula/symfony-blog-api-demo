<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\SignupRequest;
use App\Entity\User;
use App\Exception\UserAlreadyExistsException;
use App\Repository\UserRepository;
use App\Service\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationServiceTest extends TestCase
{
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private UserRegistrationService $service;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $this->service = new UserRegistrationService(
            $this->userRepository,
            $this->entityManager,
            $this->passwordHasher
        );
    }

    public function testRegisterUserCreatesAndPersistsNewUser(): void
    {
        $email = 'test@example.com';
        $plainPassword = 'secret';

        $signupRequest = new SignupRequest($email, $plainPassword);

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn(null);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), $plainPassword)
            ->willReturn('hashed-password');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->service->registerUser($signupRequest);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($email, $user->getEmail());
        $this->assertSame('hashed-password', $user->getPassword());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testRegisterUserThrowsWhenUserAlreadyExists(): void
    {
        $email = 'exists@example.com';
        $signupRequest = new SignupRequest($email, 'irrelevant');

        $this->userRepository
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn(new User());

        $this->expectException(UserAlreadyExistsException::class);
        $this->expectExceptionMessage(sprintf('User with email "%s" already exists', $email));

        $this->service->registerUser($signupRequest);
    }
}
