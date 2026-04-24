<?php

namespace App\Tests;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $_ENV['APP_ENV']    = 'test';
        $_SERVER['APP_ENV'] = 'test';

        $this->client = static::createClient();

        $env = static::getContainer()->getParameter('kernel.environment');
        if ($env !== 'test') {
            throw new \RuntimeException(
                "Les tests doivent tourner en environnement 'test', environnement actuel : '{$env}'"
            );
        }

        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        // ✅ Nettoyer via Doctrine (respecte les contraintes FK)
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        // ✅ Supprimer dans le bon ordre pour respecter les FK
        $this->em->createQuery('DELETE FROM App\Entity\Comment c')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Article a')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Category c')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\User u')->execute();

        // ✅ Réinitialiser les séquences PostgreSQL
        $connection = $this->em->getConnection();
        $connection->executeStatement('ALTER SEQUENCE comment_id_seq RESTART WITH 1');
        $connection->executeStatement('ALTER SEQUENCE article_id_seq RESTART WITH 1');
        $connection->executeStatement('ALTER SEQUENCE category_id_seq RESTART WITH 1');
        $connection->executeStatement('ALTER SEQUENCE user_id_seq RESTART WITH 1');

        $this->em->clear();
    }

    protected function getToken(string $email, string $password = 'password123'): string
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password])
        );

        $data = json_decode($this->client->getResponse()->getContent(), true);

        if (!isset($data['token'])) {
            throw new \RuntimeException(
                'Token non trouvé dans la réponse : ' . json_encode($data)
            );
        }

        return $data['token'];
    }

    protected function authenticatedRequest(
        string $method,
        string $url,
        string $token,
        array $data = []
    ): void {
        $this->client->request(
            $method,
            $url,
            [],
            [],
            [
                'CONTENT_TYPE'       => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            empty($data) ? null : json_encode($data)
        );
    }

    protected function createTestUser(
        string $email,
        string $username,
        string $password = 'password123',
        array $roles = ['ROLE_USER']
    ): User {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setLastName('Test');
        $user->setFirstName('User');
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setRoles($roles);
        $user->setPassword($hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        $this->em->clear();
        parent::tearDown();
    }
}
