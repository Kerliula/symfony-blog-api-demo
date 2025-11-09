<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\SignupRequest;
use App\Service\UserRegistrationServiceInterface;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    private const RESPONSE_KEYS = [
        'message' => 'message',
        'user' => 'user',
        'id' => 'id',
        'email' => 'email',
    ];
    private const FORMAT = 'json';

    public function __construct(
        private readonly UserRegistrationServiceInterface $registrationService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/signup', name: 'signup', methods: ['POST'])]
    public function signup(Request $request): JsonResponse
    {
        $signupRequest = $this->serializer->deserialize(
            $request->getContent(),
            SignupRequest::class,
            self::FORMAT,
        );

        $errors = $this->validator->validate($signupRequest);

        if (count($errors) > 0) {
            $errorDetails = [];

            foreach ($errors as $error) {
                $errorDetails[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'error' => 'Validation failed',
                'details' => $errorDetails,
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->registrationService->registerUser($signupRequest);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            self::RESPONSE_KEYS['message'] => 'User created successfully!',
            self::RESPONSE_KEYS['user'] => [
                self::RESPONSE_KEYS['id'] => $user->getId(),
                self::RESPONSE_KEYS['email'] => $user->getEmail(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/signin', name: 'signin', methods: ['POST'])]
    public function signin(): never
    {
        throw new LogicException('This method should never be called directly. '
            . 'Authentication is handled by the json_login authenticator in security.yaml');
    }
}
