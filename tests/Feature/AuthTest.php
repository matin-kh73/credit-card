<?php

namespace App\Tests\Feature;

use App\Controller\AuthController;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\{KernelBrowser, Test\WebTestCase};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthTest extends WebTestCase
{
    private string $path = '/login';
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->setupTestData();
    }

    private function setupTestData(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $hashedPassword = $this->passwordHasher->hashPassword($user, 'password123');
        $user->setPassword($hashedPassword);
        $user->setCreatedAt(new DateTimeImmutable());
        $user->setUpdatedAt(new DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @return void
     * @see AuthController::logout()
     */
    public function testLoginPageRendering(): void
    {
        $this->client->request('GET', $this->path);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Please sign in');
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="email"]');
        $this->assertSelectorExists('input[name="password"]');
        $this->assertSelectorExists('input[name="_csrf_token"]');
    }

    /**
     * @return void
     * @see AuthController::logout()
     */
    public function testLoginRedirectWhenAlreadyLoggedIn(): void
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $this->client->loginUser($user);
        $this->client->request('GET', $this->path);
        $this->assertResponseRedirects('/credit-cards');
    }

    /**
     * @return void
     * @see AuthController::logout()
     */
    public function testLogoutRedirectsToCreditCards(): void
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $this->client->loginUser($user);
        $this->client->request('GET', '/logout');
        $this->assertResponseRedirects('/credit-cards');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->createQueryBuilder()->delete(User::class, 'c')->getQuery()->execute();

        $this->entityManager->close();
        unset($this->entityManager);
    }
}
