<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Entity\User;
use App\Service\UserRegistrationServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends WebTestCase
{
    public function testSignupWithValidDataReturns201(): void
    {
        $client = static::createClient();

        $mockService = $this->createMock(UserRegistrationServiceInterface::class);

        $mockUser = $this->createMock(User::class);
        $mockUser->method('getId')->willReturn(1);
        $mockUser->method('getEmail')->willReturn('newuser@example.com');

        $mockService->method('registerUser')->willReturn($mockUser);

        $client->getContainer()->set(UserRegistrationServiceInterface::class, $mockService);

        $payload = [
            'email' => 'newuser@example.com',
            'password' => 'securePassword123',
        ];

        $client->request(
            'POST',
            '/api/auth/signup',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('User created successfully!', $data['message']);
        $this->assertEquals('newuser@example.com', $data['user']['email']);
        $this->assertEquals(1, $data['user']['id']);
    }

    public function testSignupWithInvalidDataReturns400(): void
    {
        $client = static::createClient();

        $payload = [
            'email' => '',
            'password' => '',
        ];

        $client->request(
            'POST',
            '/api/auth/signup',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response = $client->getResponse();

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Validation failed', $data['error']);
        $this->assertArrayHasKey('details', $data);
    }

    public function testSigninReturnsServerError(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/signin');

        $response = $client->getResponse();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString(
            'This method should never be called directly',
            $response->getContent()
        );
    }
}
